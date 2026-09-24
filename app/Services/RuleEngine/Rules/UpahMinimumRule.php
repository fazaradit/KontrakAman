<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class UpahMinimumRule implements RuleInterface {
    // Keterbatasan MVP: Harusnya ambil UMK/UMP dari tabel/API, di-hardcode ke UMP Jatim terbaru sebagai contoh.
    const UMP_REFERENCE = 2165244; 

    public function getLegalBasis(string $contractType): string {
        return "Pasal 88E UU 13/2003";
    }

    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'upah') {
            $nominal = $clause['extracted_values']['nominal'] ?? null;
            
            if ($nominal === null) {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Nominal upah tidak terdeteksi secara otomatis, perlu direview manual."
                );
            }
            
            if ($nominal < self::UMP_REFERENCE) {
                return new Violation(
                    $this->getLegalBasis($contractType),
                    "high",
                    $clause['category'],
                    "Upah yang diberikan di bawah Upah Minimum Provinsi/Kabupaten/Kota."
                );
            }
        }
        return null;
    }
}
