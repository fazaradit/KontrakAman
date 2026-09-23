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
        
        // Cek bahwa kedua finding masa_percobaan punya pasal berbeda (Ayat 1 dan Ayat 2)
        $masaPercobaanPasals = [];
        foreach ($result['findings'] as $finding) {
            if ($finding['severity'] === 'high' && strpos($finding['legal_basis'], 'Pasal 58') !== false) {
                $masaPercobaanPasals[] = $finding['pasal'];
            }
        }
        $this->assertCount(2, $masaPercobaanPasals, "Harusnya ada 2 violation masa percobaan (Ayat 1 dan Ayat 2).");
        $this->assertNotEquals($masaPercobaanPasals[0], $masaPercobaanPasals[1], "Pasal/Ayat pada 2 finding yang sama tidak boleh identik (harus Ayat 1 dan Ayat 2).");
    }

    /**
     * @group integration
     */
    public function testPKWTTDetection() {
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
        $fixtureText = file_get_contents(__DIR__ . '/fixtures/fixture_pkwtt_short.txt');
        
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO contracts (filename, raw_text, status) VALUES (?, ?, 'processing') RETURNING id");
        $stmt->execute(['fixture_pkwtt_short.txt', $fixtureText]);
        $contractId = $stmt->fetch()['id'];
        
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('getBody')->willReturn(['contract_id' => $contractId]);
        $requestMock->method('getFiles')->willReturn([]);
        
        $responseMock = new class extends Response {
            public $responseData = null;
            public function json(array $data, int $statusCode = 200) {
                $this->responseData = $data;
                return true;
            }
        };
        
        $endpoint->handle($requestMock, $responseMock);
        
        $result = $responseMock->responseData;
        $this->assertIsArray($result);
        $this->assertEquals('PKWTT', $result['contract_type'], "Gagal mendeteksi PKWTT dari singkatan PKWTT, mungkin terdeteksi sebagai PKWT");
    }
}
