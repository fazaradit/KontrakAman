<?php

namespace App\Services\Ingestion;

class TextNormalizer {
    public function normalize(string $rawText): string {
        // 1. Gabungkan baris yang terpotong di tengah kalimat
        //    (regex: '/([a-z,])\n(?=[a-z])/u')
        $text = preg_replace('/([a-z,])\n(?=[a-z])/u', '$1 ', $rawText);
        
        // 2. Normalisasi whitespace ganda/tab/newline berlebih jadi spasi tunggal,
        //    tapi kita mau mempertahankan newline antar paragraf/pasal.
        //    Wait, requirements say: "Normalisasi whitespace ganda/tab jadi spasi tunggal"
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // 3. Normalisasi variasi "PASAL"/"pasal" jadi "Pasal" (case-insensitive)
        $text = preg_replace('/(?i)pasal/', 'Pasal', $text);
        
        return $text;
    }
}
