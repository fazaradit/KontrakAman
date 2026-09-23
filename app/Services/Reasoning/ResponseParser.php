<?php
namespace App\Services\Reasoning;

class ResponseParser {
    public function parse(string $rawResponse, array $retrievedRegulations): array {
        $json = json_decode($rawResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ResponseParsingException("Response bukan JSON valid: " . json_last_error_msg() . "\nRaw: " . $rawResponse);
        }
        
        $requiredFields = ['verdict', 'cited_pasal', 'cited_source_law', 'explanation_plain_language', 'severity'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $json)) {
                throw new ResponseParsingException("Field required hilang: {$field}\nRaw: " . $rawResponse);
            }
        }
        
        $validVerdicts = ['compliant', 'violation', 'ambiguous'];
        if (!in_array($json['verdict'], $validVerdicts)) {
            throw new ResponseParsingException("Verdict tidak valid: {$json['verdict']}\nRaw: " . $rawResponse);
        }
        
        $validSeverities = ['high', 'medium', 'low'];
        if (!in_array($json['severity'], $validSeverities)) {
            throw new ResponseParsingException("Severity tidak valid: {$json['severity']}\nRaw: " . $rawResponse);
        }
        
        if ($json['verdict'] !== 'ambiguous' && $json['cited_pasal'] !== null && $json['cited_source_law'] !== null) {
            $found = false;
            $citedPasal = strtolower(trim($json['cited_pasal']));
            $citedSourceLaw = strtolower(trim($json['cited_source_law']));
            
            foreach ($retrievedRegulations as $reg) {
                $sourceLaw = strtolower(trim($reg['source_law'] ?? ''));
                $pasal = strtolower(trim($reg['pasal'] ?? ''));
                $ayat = strtolower(trim($reg['ayat'] ?? ''));
                $pasalFull = trim($pasal . ($ayat ? " $ayat" : ""));
                
                if ($sourceLaw === $citedSourceLaw) {
                    if ($pasalFull === $citedPasal || $pasal === $citedPasal || strpos($pasalFull, $citedPasal) !== false || strpos($citedPasal, $pasal) !== false) {
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                throw new GroundingViolationException("LLM mengutip pasal yang tidak ada di konteks: {$json['cited_pasal']} dari {$json['cited_source_law']}");
            }
        }
        
        return $json;
    }
}
