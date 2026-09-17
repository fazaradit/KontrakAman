<?php
namespace App\Services\RuleEngine;

interface RuleInterface {
    public function evaluate(array $clause, string $contractType): ?Violation;
}
