<?php
namespace App\Services\RuleEngine\Rules;

use App\Services\RuleEngine\RuleInterface;
use App\Services\RuleEngine\Violation;

class DurasiPkwtRule implements RuleInterface {
    public function getLegalBasis(string $contractType): string {
        return "Pasal 8 PP 35/2021";
    }

    public function evaluate(array $clause, string $contractType): ?Violation {
        if ($clause['category'] === 'durasi_pkwt') {
            if ($contractType === 'UNKNOWN') {
                return new Violation(
                    "Review Manual",
                    "low",
                    $clause['category'],
                    "Jenis kontrak (PKWT/PKWTT) tidak terdeteksi otomatis dari dokumen ini, sehingga pengecekan durasi_pkwt memerlukan verifikasi manual.",
                    "rule_engine",
                    "ambiguous"
                );
            } elseif ($contractType === 'PKWT') {
                $durasiBulan = $clause['extracted_values']['durasi_bulan'] ?? null;
                
                if ($durasiBulan === null) {
                    return new Violation(
                        $this->getLegalBasis($contractType),
                        "low",
                        $clause['category'],
                        "Perlu review manual, durasi tidak terdeteksi otomatis."
                    );
                }
                
                if ($durasiBulan > 60) {
                    return new Violation(
                        $this->getLegalBasis($contractType),
                        "high",
                        $clause['category'],
                        "Durasi PKWT (termasuk perpanjangan) maksimal 5 tahun (60 bulan)."
                    );
                }
            }
        }
        return null;
    }
}
