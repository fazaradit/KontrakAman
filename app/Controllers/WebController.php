<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Config\Database;
use App\Services\Ingestion\PdfExtractor;

class WebController {
    public function showUploadForm(Request $request, Response $response) {
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../Views/upload.php';
        return $response;
    }

    public function handleUpload(Request $request, Response $response) {
        $files = $request->getFiles();
        
        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            header("Location: /upload?error=" . urlencode("File upload gagal atau tidak ada file."));
            exit;
        }
        
        $file = $files['file'];
        
        if ($file['type'] !== 'application/pdf') {
            header("Location: /upload?error=" . urlencode("Hanya menerima file PDF."));
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            header("Location: /upload?error=" . urlencode("Ukuran file melebihi batas 5MB."));
            exit;
        }
        
        $fileHash = hash_file('sha256', $file['tmp_name']);
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("SELECT id FROM contracts WHERE file_hash = ? AND status = 'analyzed' LIMIT 1");
        $stmt->execute([$fileHash]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $contractId = $existing['id'];
            $skipProcessing = true;
        } else {
            $extractor = new PdfExtractor();
            try {
                $text = $extractor->extract($file['tmp_name']);
            } catch (\Exception $e) {
                header("Location: /upload?error=" . urlencode("Gagal membaca PDF: " . $e->getMessage()));
                exit;
            }
            
            if (trim($text) === '') {
                header("Location: /upload?error=" . urlencode("Teks dokumen kosong setelah diekstrak."));
                exit;
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
            $contractId = $stmt->fetch()['id'];
            $skipProcessing = false;
        }
        
        if (!$skipProcessing) {
            // Jalankan pipeline analysis secara synchronous
            $analysisController = new AnalysisController();
            $result = $analysisController->runPipeline($contractId);
            
            if (isset($result['error'])) {
                header("Location: /upload?error=" . urlencode("Gagal menganalisis kontrak: " . $result['error']));
                exit;
            }
        }
        
        header("Location: /report/" . $contractId);
        exit;
    }

    public function showReport(Request $request, Response $response, string $contractId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $response->setStatusCode(404);
            echo "<h1>404 Not Found</h1><p>Kontrak tidak ditemukan.</p>";
            return $response;
        }
        
        // Get all findings
        $stmt = $pdo->prepare("
            SELECT ar.*, cc.clause_number, cc.raw_text as clause_text, cc.category
            FROM analysis_results ar
            LEFT JOIN contract_clauses cc ON ar.clause_id = cc.id
            WHERE cc.contract_id = ?
            ORDER BY
                CASE ar.severity
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                    ELSE 4
                END
        ");
        $stmt->execute([$contractId]);
        $findings = $stmt->fetchAll();
        
        // Count total checked clauses
        $stmt = $pdo->prepare("SELECT count(*) FROM contract_clauses WHERE contract_id = ? AND category IS NOT NULL");
        $stmt->execute([$contractId]);
        $totalClauses = $stmt->fetchColumn();
        
        require_once __DIR__ . '/../Views/report.php';
        return $response;
    }
}
