<?php

namespace App\Services\Ingestion;

use Exception;

class PdfExtractor {
    public function __construct() {
    }

    public function extract(string $filePath): string {
        try {
            // Kita menggunakan poppler-utils (pdftotext) untuk menghindari bug duplikasi teks
            // (faux-bolding/font-subsetting) yang sering terjadi di Smalot/pdfparser
            $outputFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.txt';
            
            // Execute pdftotext with -layout to maintain logical structure and avoid overlap duplication
            $command = sprintf("pdftotext -layout %s %s", escapeshellarg($filePath), escapeshellarg($outputFile));
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0 || !file_exists($outputFile)) {
                throw new PdfExtractionException("pdftotext gagal dijalankan. Exit code: " . $returnVar);
            }
            
            $text = file_get_contents($outputFile);
            unlink($outputFile);
            
            if (empty(trim($text)) || strlen(trim($text)) < 50) {
                throw new PdfExtractionException("Teks yang diekstrak kosong atau terlalu pendek (<50 karakter).");
            }
            
            return $text;
        } catch (PdfExtractionException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new PdfExtractionException("Gagal membaca file PDF: " . $e->getMessage(), 0, $e);
        }
    }
}
