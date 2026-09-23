<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Config\Database;
use App\Services\Ingestion\TextNormalizer;
use App\Services\Segmentation\ClauseSegmenter;
use App\Services\Segmentation\AyatSegmenter;
use App\Services\Segmentation\ClauseCategorizer;
use App\Services\RuleEngine\ValueExtractor;
use App\Services\RuleEngine\RuleEngineRunner;
use App\Services\RuleEngine\Violation;
use App\Services\Retrieval\HybridSearch;
use App\Services\Reasoning\ReasoningOrchestrator;
use App\Services\Reasoning\GeminiClient;
use App\Services\Reasoning\PromptBuilder;
use App\Services\Reasoning\ResponseParser;

class AnalysisController {
    public function analyze(Request $request, Response $response, string $contractId) {
        $result = $this->runPipeline((int)$contractId);
        
        if (isset($result['error'])) {
            $code = $result['status_code'] ?? 500;
            return $response->json(['error' => $result['error']], $code);
        }
        
        return $response->json($result);
    }
    
    public function runPipeline(int $contractId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            return ['error' => 'Kontrak tidak ditemukan', 'status_code' => 404];
        }
        
        $normalizer = new TextNormalizer();
        $clauseSegmenter = new ClauseSegmenter();
        $ayatSegmenter = new AyatSegmenter();
        $categorizer = new ClauseCategorizer();
        $valueExtractor = new ValueExtractor();
        
        $normalizedText = $normalizer->normalize($contract['raw_text']);
        $pasalBlocks = $clauseSegmenter->extractPasalBlocks($normalizedText);
        
        $contractType = 'UNKNOWN';
        
        $isPKWTT = stripos($normalizedText, 'waktu tidak tertentu') !== false || stripos($normalizedText, 'pkwtt') !== false;
        
        if ($isPKWTT) {
            $contractType = 'PKWTT';
        } else {
            // Cek PKWT HANYA jika bukan PKWTT
            $isPKWT = stripos($normalizedText, 'waktu tertentu') !== false || stripos($normalizedText, 'pkwt') !== false;
            if ($isPKWT) {
                $contractType = 'PKWT';
            }
        }
        
        $pdo->prepare("UPDATE contracts SET contract_type = ? WHERE id = ?")->execute([$contractType, $contractId]);
        
        $allClauses = [];
        $order = 1;
        
        // Simpan klausul ke contract_clauses
        foreach ($pasalBlocks as $pasal) {
            $ayats = $ayatSegmenter->extractAyatFromPasal($pasal['raw_block']);
            
            // Default to "Pasal {$pasal['pasal_number']}" if title is missing
            $pasalTitle = !empty($pasal['title']) ? $pasal['title'] : "Pasal " . ($pasal['pasal_number'] ?? $order);
            
            foreach ($ayats as $ayatArray) {
                $ayatStr = $ayatArray['text'];
                
                $category = $categorizer->categorize($ayatStr);
                $extractedValues = null;
                if ($category) {
                    $extractedValues = $valueExtractor->extract($category, $ayatStr);
                }
                
                // Construct the exact clause number string e.g., "Pasal 5 Ayat 1" or just "Pasal 5"
                $clauseNumberStr = $pasalTitle;
                if (count($ayats) > 1 && $ayatArray['ayat_number'] !== null) {
                    $clauseNumberStr .= " Ayat " . $ayatArray['ayat_number'];
                }
                
                $stmt = $pdo->prepare("INSERT INTO contract_clauses (contract_id, clause_number, raw_text, category, extracted_values, position_order) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
                $stmt->execute([
                    $contractId,
                    $clauseNumberStr,
                    $ayatStr,
                    $category,
                    $extractedValues ? json_encode($extractedValues) : null,
                    $order
                ]);
                
                $clauseId = $stmt->fetch()['id'];
                
                $allClauses[] = [
                    'id' => $clauseId,
                    'clause_number' => $clauseNumberStr,
                    'raw_text' => $ayatStr,
                    'category' => $category,
                    'extracted_values' => $extractedValues
                ];
                $order++;
            }
        }
        
        // Setup engine
        $ruleEngine = new RuleEngineRunner();
        $hybridSearch = new HybridSearch();
        $orchestrator = new ReasoningOrchestrator(new GeminiClient(), new PromptBuilder(), new ResponseParser());
        
        // Ambil hasil rule engine untuk semua klausul
        $ruleViolations = $ruleEngine->run($allClauses, $contractType);
        $processedCategories = [];
        
        $analysisResults = [];
        
        // Rule Engine violations (deterministik)
        foreach ($ruleViolations as $violation) {
            $processedCategories[] = $violation->category;
            
            // Find clause number from clauseId or fallback to category
            $clauseNumStr = "Umum / Tidak ditemukan klausul";
            $clauseIdFound = $violation->clauseId ?? null;
            
            if ($clauseIdFound) {
                foreach ($allClauses as $c) {
                    if ($c['id'] === $clauseIdFound) {
                        $clauseNumStr = $c['clause_number'];
                        break;
                    }
                }
            } else {
                foreach ($allClauses as $c) {
                    if ($c['category'] === $violation->category) {
                        $clauseNumStr = $c['clause_number'];
                        $clauseIdFound = $c['id'];
                        break;
                    }
                }
            }
            
            // Insert into analysis_results
            $stmt = $pdo->prepare("INSERT INTO analysis_results (clause_id, verdict, severity, source, explanation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $clauseIdFound, // Use found clause_id instead of null
                'violation',
                $violation->severity,
                'rule_engine',
                $violation->message
            ]);
            
            $analysisResults[] = [
                'clause_id' => $clauseIdFound,
                'clause_number' => $clauseNumStr,
                'verdict' => 'violation',
                'severity' => $violation->severity,
                'category' => $violation->category,
                'explanation' => $violation->message,
                'source' => 'rule_engine',
                'legal_basis' => $violation->pasal
            ];
        }
        
        // Untuk klausul ambigu / belum diproses RuleEngine tapi punya kategori
        foreach ($allClauses as $clause) {
            if ($clause['category'] !== null && !in_array($clause['category'], $processedCategories)) {
                try {
                    $retrieved = $hybridSearch->retrieve($clause);
                    if (count($retrieved) > 0) {
                        $llmResult = $orchestrator->process($clause, $retrieved);
                        
                        $stmt = $pdo->prepare("INSERT INTO analysis_results (clause_id, matched_regulation_ids, verdict, severity, source, explanation, confidence) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $clause['id'],
                            '{' . implode(',', $llmResult['matched_regulation_ids'] ?? []) . '}',
                            $llmResult['verdict'],
                            $llmResult['severity'],
                            $llmResult['source'],
                            $llmResult['explanation'],
                            $llmResult['confidence']
                        ]);
                        
                        // Parse cited pasal dari raw_response jika bisa, atau pass null
                        $cited = 'Review Manual';
                        if (!empty($llmResult['raw_response'])) {
                            $decoded = json_decode($llmResult['raw_response'], true);
                            if ($decoded && isset($decoded['cited_pasal'])) {
                                $cited = $decoded['cited_pasal'] . ($decoded['cited_source_law'] ? ' ' . $decoded['cited_source_law'] : '');
                            }
                        }
                        
                        $analysisResults[] = [
                            'clause_id' => $clause['id'],
                            'clause_number' => $clause['clause_number'],
                            'verdict' => $llmResult['verdict'],
                            'severity' => $llmResult['severity'],
                            'category' => $clause['category'],
                            'explanation' => $llmResult['explanation'],
                            'source' => 'llm',
                            'legal_basis' => $cited
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignore or log error
                }
            }
        }
        
        $pdo->prepare("UPDATE contracts SET status = 'analyzed' WHERE id = ?")->execute([$contractId]);
        
        usort($analysisResults, function($a, $b) {
            $order = ['high' => 3, 'medium' => 2, 'low' => 1];
            $aSev = $order[$a['severity'] ?? 'low'] ?? 0;
            $bSev = $order[$b['severity'] ?? 'low'] ?? 0;
            return $bSev <=> $aSev;
        });
        
        return [
            'contract_id' => $contractId,
            'contract_type' => $contractType,
            'total_clauses_checked' => count($allClauses),
            'violations_found' => count(array_filter($analysisResults, fn($r) => $r['verdict'] === 'violation')),
            'findings' => $analysisResults
        ];
    }
}
