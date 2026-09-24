<?php
namespace App\Services\RuleEngine;

interface RuleInterface {
    public function getLegalBasis(string $contractType): string;
    public function evaluate(array $clause, string $contractType): ?Violation;
}
