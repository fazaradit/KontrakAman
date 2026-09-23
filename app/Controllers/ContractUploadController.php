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
        
        $extractor = new PdfExtractor();
        try {
            $text = $extractor->extract($file['tmp_name']);
        } catch (\Exception $e) {
            return $response->json(['error' => 'Gagal membaca PDF: ' . $e->getMessage()], 500);
        }
        
        if (trim($text) === '') {
            return $response->json(['error' => 'Teks dokumen kosong setelah diekstrak.'], 422);
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO contracts (filename, raw_text, status) VALUES (?, ?, 'processing') RETURNING id");
        $stmt->execute([$file['name'], $text]);
        $row = $stmt->fetch();
        
        // Untuk MVP, proses analysis dapat dilakukan secara terpisah (async/queue di production) 
        // namun untuk saat ini client akan hit endpoint /analyze berikutnya secara terpisah,
        // ATAU via AgentToolEndpoint (synchronous).
        return $response->json([
            'contract_id' => $row['id'],
            'status' => 'processing'
        ]);
    }
}
