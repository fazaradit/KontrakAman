<?php
namespace App\Services\RuleEngine;

class Violation {
    public string $pasal;
    public string $severity; // high | medium | low
    public string $category;
    public string $message;
    public string $source;

    public function __construct(string $pasal, string $severity, string $category, string $message, string $source = 'rule_engine') {
        $this->pasal = $pasal;
        $this->severity = $severity;
        $this->category = $category;
        $this->message = $message;
        $this->source = $source;
    }
}
