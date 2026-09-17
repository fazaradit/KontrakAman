<?php

namespace App\Services\Segmentation;

class AyatSegmenter {
    public function extractAyatFromPasal(string $pasalBlock): array {
        // Pattern: (1), (2), etc at the beginning of a line (allowing leading spaces)
        // Or sometimes it's not strictly at the beginning but after a newline or whitespace
        $pattern = '/^\s*\((\d+)\)\s*/m';
        
        $parts = preg_split($pattern, $pasalBlock, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $ayatList = [];
        $currentAyatNumber = null;
        $currentText = '';
        
        // If there's some text before the first ayat (like the title "Pasal 1\nTentang..."), 
        // it will be the first item if it doesn't match the delimiter.
        // Wait, PREG_SPLIT_DELIM_CAPTURE will capture the (\d+) as a separate element.
        
        $isFirstPart = true;
        
        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];
            
            // If this part is a number and matches our expected next part in regex capture
            // It's a bit tricky to know if $part is the captured number or text.
            // Using a better approach: preg_match_all to find the delimiters and offsets
            
            // Let's fallback to regex match all with offset capture for safer parsing
        }
        
        // Safer implementation:
        if (preg_match_all($pattern, $pasalBlock, $matches, PREG_OFFSET_CAPTURE)) {
            $count = count($matches[0]);
            
            for ($i = 0; $i < $count; $i++) {
                $ayatNumber = (int) $matches[1][$i][0];
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
