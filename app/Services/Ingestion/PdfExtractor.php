<?php

namespace App\Services\Ingestion;

use Smalot\PdfParser\Parser;
use Exception;

class PdfExtractor {
    private Parser $parser;

    public function __construct() {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): string {
        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();
            
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
