<?php

namespace App\Services\Segmentation;

class ClauseCategorizer {
    public function categorize(string $clauseText): ?string {
        $text = strtolower($clauseText);
        
        $categories = [
            'masa_percobaan' => ['masa percobaan', 'probation'],
            'durasi_pkwt' => ['jangka waktu', 'berlaku selama', 'berakhir pada', 'durasi kontrak'],
            'lembur' => ['lembur', 'overtime', 'waktu kerja tambahan'],
            'upah' => ['upah', 'gaji', 'imbalan', 'honorarium'],
            'pesangon' => ['pesangon', 'uang penghargaan', 'uang penggantian hak', 'kompensasi akhir'],
            'cuti' => ['cuti', 'istirahat tahunan']
        ];
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    return $category;
                }
            }
        }
        
        return null;
    }
}
