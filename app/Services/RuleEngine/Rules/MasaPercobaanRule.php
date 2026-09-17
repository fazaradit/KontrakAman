<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class MasaPercobaanRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($contractType === 'PKWT' && $clause['category'] === 'masa_percobaan') {
            return new Violation(
                "Pasal 58 UU 13/2003",
                "high",
                $clause['category'],
                "PKWT tidak boleh memiliki masa percobaan, klausul batal demi hukum."
            );
        }
        return null;
    }
}
