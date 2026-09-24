<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class MasaPercobaanRule implements RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'masa_percobaan') {
            if ($contractType === 'UNKNOWN') {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Jenis kontrak (PKWT/PKWTT) tidak terdeteksi otomatis dari dokumen ini, sehingga pengecekan masa_percobaan memerlukan verifikasi manual.",
                    "rule_engine",
                    "ambiguous"
                );
            } elseif ($contractType === 'PKWT') {
                return new Violation(
                    "Pasal 58 UU 13/2003",
                    "high",
                    $clause['category'],
                    "PKWT tidak boleh memiliki masa percobaan, klausul batal demi hukum."
                );
            } elseif ($contractType === 'PKWTT') {
                $durasiBulan = $clause['extracted_values']['durasi_bulan'] ?? null;
                if ($durasiBulan === null) {
                    return new Violation(
                        "Review Manual",
                        "low",
                        $clause['category'],
                        "Masa percobaan untuk PKWTT tidak terdeteksi batas waktunya secara otomatis."
                    );
                } elseif ($durasiBulan > 3) {
                    return new Violation(
                        "Pasal 60 UU 13/2003",
                        "medium",
                        $clause['category'],
                        "Masa percobaan untuk PKWTT maksimal 3 bulan."
                    );
                }
            }
        }
        return null;
    }
}
