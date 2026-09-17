<?php
namespace App\Services\RuleEngine;

use App\Services\RuleEngine\Rules\MasaPercobaanRule;
use App\Services\RuleEngine\Rules\DurasiPkwtRule;
use App\Services\RuleEngine\Rules\LemburRule;
use App\Services\RuleEngine\Rules\UpahMinimumRule;
use App\Services\RuleEngine\Rules\CutiTahunanRule;
use App\Services\RuleEngine\Rules\PesangonRule;

class RuleEngineRunner {
    private ValueExtractor $extractor;
    private array $rules = [];

    public function __construct() {
        $this->extractor = new ValueExtractor();
        
        $this->rules = [
            'masa_percobaan' => new MasaPercobaanRule(),
            'durasi_pkwt' => new DurasiPkwtRule(),
            'lembur' => new LemburRule(),
            'upah' => new UpahMinimumRule(),
            'cuti' => new CutiTahunanRule(),
            'pesangon' => new PesangonRule(),
        ];
    }

    public function run(array $clauses, string $contractType): array {
        $violations = [];
        $foundCuti = false;
        
        foreach ($clauses as $clause) {
            $category = $clause['category'] ?? null;
            
            if ($category === null) continue;
            
            if ($category === 'cuti') {
                $foundCuti = true;
            }
            
            if (isset($this->rules[$category])) {
                $clause['extracted_values'] = $this->extractor->extract($category, $clause['raw_text']);
                
                $violation = $this->rules[$category]->evaluate($clause, $contractType);
                if ($violation !== null) {
                    $violations[] = $violation;
                }
            }
        }
        
        // Cek cuti tahunan tidak ada
        if (!$foundCuti) {
            $violations[] = new Violation(
                "Pasal 79 ayat (2) UU 13/2003",
                "medium",
                "cuti",
                "Hak cuti tahunan tidak dicantumkan dalam kontrak."
            );
        }
        
        // Sort violations by severity (high > medium > low)
        usort($violations, function(Violation $a, Violation $b) {
            $order = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $order[$b->severity] <=> $order[$a->severity];
        });
        
        return $violations;
    }
}
