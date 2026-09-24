<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class PesangonRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'pesangon') {
            if ($contractType === 'UNKNOWN') {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Jenis kontrak (PKWT/PKWTT) tidak terdeteksi otomatis dari dokumen ini, sehingga pengecekan pesangon memerlukan verifikasi manual.",
                    "rule_engine",
                    "ambiguous"
                );
            } elseif ($contractType === 'PKWTT') {
                $multiplier = $clause['extracted_values']['multiplier'] ?? null;
                if ($multiplier === null) {
                    return new Violation(
                        "Review Manual",
                        "low",
                        $clause['category'],
                        "Multiplier pesangon tidak terdeteksi, perlu direview manual."
                    );
                } else {
                    return new Violation(
                        "PP 35/2021",
                        "low",
                        $clause['category'],
                        "Perlu verifikasi manual terhadap tabel pesangon PP 35/2021."
                    );
                }
            }
        }
        return null;
    }
}
