<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class LemburRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'lembur') {
            $jamPerHari = $clause['extracted_values']['jam_per_hari'] ?? null;
            if ($jamPerHari !== null && $jamPerHari > 3) { 
                return new Violation(
                    "Kepmenaker No. 102/MEN/VI/2004",
                    "medium",
                    $clause['category'],
                    "Waktu lembur maksimal 3 jam sehari menurut Kepmenaker lama."
                );
            }
        }
        return null;
    }
}
