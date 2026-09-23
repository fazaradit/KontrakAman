<?php

use PHPUnit\Framework\TestCase;
use App\Services\HermesGateway\AgentToolEndpoint;
use App\Core\Request;
use App\Core\Response;

class IntegrationPipelineTest extends TestCase {
    
    /**
     * @group integration
     */
    public function testEndToEndHermesEndpoint() {
        // Load env variables if not loaded
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
                putenv(trim($name) . '=' . trim($value));
            }
        }
        
        $endpoint = new AgentToolEndpoint();
        
        $fixtureText = file_get_contents(__DIR__ . '/fixtures/fixture_1_format_standar.txt');
        
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO contracts (filename, raw_text, status) VALUES (?, ?, 'processing') RETURNING id");
        $stmt->execute(['fixture_1.txt', $fixtureText]);
        $contractId = $stmt->fetch()['id'];
        
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('getBody')->willReturn(['contract_id' => $contractId]);
        $requestMock->method('getFiles')->willReturn([]);
        
        // Untuk mock response json dan tidak exit
        $responseMock = new class extends Response {
            public $responseData = null;
            public function json(array $data, int $statusCode = 200) {
                $this->responseData = $data;
                // do not exit!
                return true;
            }
        };
        
        $endpoint->handle($requestMock, $responseMock);
        
        $result = $responseMock->responseData;
        
        echo "\n[INTEGRATION PIPELINE TEST RESULT]\n";
        print_r($result);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('findings', $result);
        $this->assertArrayHasKey('total_clauses_checked', $result);
        
        $foundHighViolation = false;
        foreach ($result['findings'] as $finding) {
            if ($finding['severity'] === 'high' && strpos($finding['legal_basis'], 'Pasal 58') !== false) {
                $foundHighViolation = true;
            }
        }
        
        $this->assertTrue($foundHighViolation, "Gagal menemukan finding dengan severity high dan basis Pasal 58.");
    }
}
