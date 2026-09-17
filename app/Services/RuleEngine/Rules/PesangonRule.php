<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class PesangonRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($contractType === 'PKWTT' && $clause['category'] === 'pesangon') {
            $multiplier = $clause['extracted_values']['multiplier'] ?? null;
            if ($multiplier !== null) {
                // TODO: verifikasi manual terhadap tabel pesangon PP 35/2021
                return new Violation(
                    "PP 35/2021",
                    "low",
                    $clause['category'],
                    "Perlu verifikasi manual terhadap tabel pesangon PP 35/2021."
                );
            }
        }
        return null;
    }
}
