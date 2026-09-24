<?php

namespace App\Services\Segmentation;

class AyatSegmenter {
    public function extractAyatFromPasal(string $pasalBlock): array {
        // Pattern: (1), (2), etc or Ayat 1, Ayat 2 at the beginning of a line (allowing leading spaces)
        $pattern = '/^\s*(?:\((\d+)\)|Ayat\s+(\d+))\s*/mi';
        
        $ayatList = [];
        
        if (preg_match_all($pattern, $pasalBlock, $matches, PREG_OFFSET_CAPTURE)) {
            $count = count($matches[0]);
            
            for ($i = 0; $i < $count; $i++) {
                // Match could be in group 1 or group 2
                $ayatNumberMatch = !empty($matches[1][$i][0]) ? $matches[1][$i][0] : $matches[2][$i][0];
                $ayatNumber = (int) $ayatNumberMatch;
                
                $startPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
                
                $endPos = ($i < $count - 1) ? $matches[0][$i+1][1] : strlen($pasalBlock);
                
                $ayatText = substr($pasalBlock, $startPos, $endPos - $startPos);
                
                $ayatList[] = [
                    'ayat_number' => $ayatNumber,
                    'text' => trim($ayatText)
                ];
            }
        }
        
        // Fallback: If no ayat detected, return the whole block as single entry
        if (empty($ayatList)) {
            return [
                [
                    'ayat_number' => null,
                    'text' => trim($pasalBlock)
                ]
            ];
        }
        
        return $ayatList;
    }
}
