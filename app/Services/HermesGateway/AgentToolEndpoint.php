<?php
namespace App\Services\HermesGateway;

use App\Core\Request;
use App\Core\Response;
use App\Config\Database;
use App\Services\Ingestion\PdfExtractor;
use App\Controllers\AnalysisController;

class AgentToolEndpoint {
    public function handle(Request $request, Response $response) {
        try {
            $body = $request->getBody();
            $files = $request->getFiles();
            
            $contractId = null;
            
            if (!empty($body['contract_id'])) {
                $contractId = (int)$body['contract_id'];
            } elseif (!empty($files['file']) && $files['file']['error'] === UPLOAD_ERR_OK) {
                $file = $files['file'];
                
                if ($file['type'] !== 'application/pdf') {
                    return $response->json(['error' => 'Hanya menerima file PDF.'], 400);
                }
                
                if ($file['size'] > 5 * 1024 * 1024) {
                    return $response->json(['error' => 'Ukuran file maksimal 5MB.'], 400);
                }
                
                $extractor = new PdfExtractor();
                $text = $extractor->extract($file['tmp_name']);
                
                if (trim($text) === '') {
                    return $response->json(['error' => 'Teks dokumen kosong setelah diekstrak.'], 422);
                }
                
                $contractType = 'UNKNOWN';
                
                $isPKWTT = stripos($text, 'waktu tidak tertentu') !== false || stripos($text, 'pkwtt') !== false;
                
                if ($isPKWTT) {
                    $contractType = 'PKWTT';
                } else {
                    $isPKWT = stripos($text, 'waktu tertentu') !== false || stripos($text, 'pkwt') !== false;
                    if ($isPKWT) {
                        $contractType = 'PKWT';
                    }
                }
                
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("INSERT INTO contracts (filename, raw_text, contract_type, status) VALUES (?, ?, ?, 'processing') RETURNING id");
                $stmt->execute([$file['name'], $text, $contractType]);
                $contractId = $stmt->fetch()['id'];
            } else {
                return $response->json(['error' => 'Harap berikan contract_id atau upload file kontrak (PDF).'], 400);
            }
            
            $analysisController = new AnalysisController();
            $result = $analysisController->runPipeline($contractId);
            
            if (isset($result['error'])) {
                return $response->json(['error' => $result['error']], $result['status_code'] ?? 500);
            }
            
            $findings = [];
            foreach ($result['findings'] as $finding) {
                if ($finding['verdict'] === 'violation') {
                    $findings[] = [
                        'pasal' => $finding['clause_number'] ?? 'Pasal Tidak Diketahui',
                        'severity' => $finding['severity'],
                        'verdict' => $finding['verdict'],
                        'legal_basis' => $finding['legal_basis'] ?? 'Hukum Ketenagakerjaan',
                        'summary' => $finding['explanation']
                    ];
                }
            }
            
            $totalClauses = $result['total_clauses_checked'];
            $violationsFound = count($findings);
            $compliantCount = $totalClauses - $violationsFound;
            if ($compliantCount < 0) $compliantCount = 0; // fallback if multiple violations per category
            
            $hermesResponse = [
                'contract_type' => $result['contract_type'],
                'total_clauses_checked' => $totalClauses,
                'violations_found' => $violationsFound,
                'findings' => $findings,
                'compliant_summary' => "$compliantCount dari {$totalClauses} klausul/kategori yang diperiksa sudah sesuai ketentuan atau tidak bermasalah."
            ];
            
            return $response->json($hermesResponse);
            
        } catch (\Exception $e) {
            return $response->json([
                'error' => 'Terjadi kesalahan saat memproses kontrak: ' . $e->getMessage()
            ], 500);
        }
    }
}
