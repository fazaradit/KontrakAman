<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Config\Database;
use App\Services\Ingestion\PdfExtractor;

class ContractUploadController {
    public function handle(Request $request, Response $response) {
        $files = $request->getFiles();
        
        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            return $response->json(['error' => 'File upload gagal atau tidak ada file.'], 400);
        }
        
        $file = $files['file'];
        
        if ($file['type'] !== 'application/pdf') {
            return $response->json(['error' => 'Hanya menerima file PDF.'], 400);
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            return $response->json(['error' => 'Ukuran file melebihi batas 5MB.'], 400);
        }
        
        $fileHash = hash_file('sha256', $file['tmp_name']);
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("SELECT id FROM contracts WHERE file_hash = ? AND status = 'analyzed' LIMIT 1");
        $stmt->execute([$fileHash]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $contractId = $existing['id'];
            $status = 'analyzed';
        } else {
            $extractor = new PdfExtractor();
            try {
                $text = $extractor->extract($file['tmp_name']);
            } catch (\Exception $e) {
                return $response->json(['error' => 'Gagal membaca PDF: ' . $e->getMessage()], 500);
            }
            
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
            
            $stmt = $pdo->prepare("INSERT INTO contracts (filename, raw_text, contract_type, status, file_hash) VALUES (?, ?, ?, 'processing', ?) RETURNING id");
            $stmt->execute([$file['name'], $text, $contractType, $fileHash]);
            $row = $stmt->fetch();
            $contractId = $row['id'];
            $status = 'processing';
        }
        
        // Untuk MVP, proses analysis dapat dilakukan secara terpisah (async/queue di production) 
        // namun untuk saat ini client akan hit endpoint /analyze berikutnya secara terpisah,
        // ATAU via AgentToolEndpoint (synchronous).
        return $response->json([
            'contract_id' => $contractId,
            'status' => $status
        ]);
    }
}
