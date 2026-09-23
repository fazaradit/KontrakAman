<?php
namespace App\Services\RuleEngine;

class ValueExtractor {
    public function extract(string $category, string $clauseText): array {
        $text = strtolower($clauseText);
        $result = [];
        
        switch ($category) {
            case 'masa_percobaan':
                if (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?bulan/', $text, $matches)) {
                    $result['durasi_bulan'] = (int)$matches[1];
                } else {
                    $result['durasi_bulan'] = null;
                }
                break;
                
            case 'durasi_pkwt':
                // Match year first
                if (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?tahun/', $text, $matches)) {
                    $result['durasi_bulan'] = ((int)$matches[1]) * 12;
                } elseif (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?bulan/', $text, $matches)) {
                    $result['durasi_bulan'] = (int)$matches[1];
                } else {
                    $result['durasi_bulan'] = null;
                }
                break;
                
            case 'lembur':
                $result['jam_per_hari'] = null;
                $result['jam_per_minggu'] = null;
                
                if (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?jam.*minggu/', $text, $matches)) {
                    $result['jam_per_minggu'] = (int)$matches[1];
                } elseif (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?jam.*hari/', $text, $matches)) {
                    $result['jam_per_hari'] = (int)$matches[1];
                } elseif (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?jam/', $text, $matches)) {
                    $result['jam_per_hari'] = (int)$matches[1];
                }
                break;
                
            case 'upah':
                $nominal = null;
                // Match Rp 3.000.000 or Rp3000000
                if (preg_match('/rp\s*([0-9\.]+)/', $text, $matches)) {
                    $nominal = (int)str_replace('.', '', $matches[1]);
                } elseif (preg_match('/(\d+)\s*(juta|ribu)/', $text, $matches)) {
                    $val = (int)$matches[1];
                    if ($matches[2] === 'juta') $nominal = $val * 1000000;
                    if ($matches[2] === 'ribu') $nominal = $val * 1000;
                }
                $result['nominal'] = $nominal;
                break;
                
            case 'cuti':
                if (preg_match('/(\d+)\s*(?:\([a-z]+\)\s*)?hari/', $text, $matches)) {
                    $result['hari_per_tahun'] = (int)$matches[1];
                } else {
                    $result['hari_per_tahun'] = null;
                }
                break;
                
            case 'pesangon':
                if (preg_match('/(\d+(?:\.\d+)?)\s*(?:\([a-z]+\)\s*)?(?:kali|bulan)\s*gaji/', $text, $matches)) {
                    $result['multiplier'] = (float)$matches[1];
                } else {
                    $result['multiplier'] = null;
                }
                break;
                
            default:
                break;
        }
        
        return $result;
    }
}
