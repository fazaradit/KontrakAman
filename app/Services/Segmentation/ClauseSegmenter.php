<?php

namespace App\Services\Segmentation;

class ClauseSegmenter {
    public function extractPasalBlocks(string $text): array {
        // Find all occurrences of "Pasal <number>" or variations
        // Using PREG_OFFSET_CAPTURE to get the start positions
        $pattern = '/^\s*Pasal\s+(\d+)/im';
        
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        
        $blocks = [];
        $count = count($matches[0]);
        
        for ($i = 0; $i < $count; $i++) {
            $pasalMatch = $matches[0][$i];
            $numberMatch = $matches[1][$i];
            
            $startPos = $pasalMatch[1];
            $pasalNumber = (int) $numberMatch[0];
            
            // Find end position (start of next pasal, or end of string)
            $endPos = ($i < $count - 1) ? $matches[0][$i + 1][1] : strlen($text);
            
            $rawBlock = substr($text, $startPos, $endPos - $startPos);
            
            $blocks[] = [
                'pasal_number' => $pasalNumber,
                'title' => trim($pasalMatch[0]),
                'raw_block' => trim($rawBlock)
            ];
        }
        
        return $blocks;
    }
}
