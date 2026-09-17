<?php

namespace App\Services\Ingestion;

use Exception;

class OcrFallback {
    public function extractViaOcr(string $filePath): string {
        throw new Exception("OCR belum diimplementasikan");
    }
}
