<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class LemburRule implements RuleInterface {
    public function getLegalBasis(string $contractType): string {
        return "Pasal 26 PP 35/2021";
    }

    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'lembur') {
            $jamPerHari = $clause['extracted_values']['jam_per_hari'] ?? null;
            $jamPerMinggu = $clause['extracted_values']['jam_per_minggu'] ?? null;
            
            if ($jamPerHari === null && $jamPerMinggu === null) {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Durasi lembur tidak terdeteksi secara otomatis, perlu direview manual."
                );
            }
            
            if (($jamPerHari !== null && $jamPerHari > 4) || ($jamPerMinggu !== null && $jamPerMinggu > 18)) { 
                return new Violation(
                    $this->getLegalBasis($contractType),
                    "medium",
                    $clause['category'],
                    "Waktu lembur maksimal 4 jam/hari dan 18 jam/minggu menurut PP 35/2021."
                );
            }
        }
        return null;
    }
}
