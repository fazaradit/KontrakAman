<?php
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
    public function testRoutesAreRegistered() {
        $indexContent = file_get_contents(__DIR__ . '/../public/index.php');
        
        $this->assertStringContainsString("post('/api/contracts/upload'", $indexContent, "Route POST /api/contracts/upload is missing");
        $this->assertStringContainsString("post('/api/contracts/{id}/analyze'", $indexContent, "Route POST /api/contracts/{id}/analyze is missing");
        $this->assertStringContainsString("post('/api/agent/analyze-contract'", $indexContent, "Route POST /api/agent/analyze-contract is missing");
    }
}
