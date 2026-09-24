<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class CutiTahunanRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'cuti') {
            $hariPerTahun = $clause['extracted_values']['hari_per_tahun'] ?? null;
            
            if ($hariPerTahun === null) {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Durasi cuti tidak terdeteksi secara otomatis, perlu direview manual."
                );
            }
            
            if ($hariPerTahun < 12) {
                return new Violation(
                    "Pasal 79 ayat (2) UU 13/2003",
                    "medium",
                    $clause['category'],
                    "Cuti tahunan minimal 12 hari kerja setelah bekerja 12 bulan terus menerus."
                );
            }
        }
        return null;
    }
}
