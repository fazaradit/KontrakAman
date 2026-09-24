<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\WebController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

class WebControllerTest extends TestCase {
    
    public function testRoutesRegistered() {
        $router = new Router();
        
        $router->get('/upload', [\App\Controllers\WebController::class, 'showUploadForm']);
        $router->post('/upload', [\App\Controllers\WebController::class, 'handleUpload']);
        $router->get('/report/{id}', [\App\Controllers\WebController::class, 'showReport']);
        
        $reflection = new \ReflectionClass($router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($router);
        
        $this->assertCount(3, $routes);
        $this->assertEquals('/upload', $routes[0]['path']);
        $this->assertEquals('GET', $routes[0]['method']);
        
        $this->assertEquals('/upload', $routes[1]['path']);
        $this->assertEquals('POST', $routes[1]['method']);
        
        $this->assertEquals('/report/{id}', $routes[2]['path']);
        $this->assertEquals('GET', $routes[2]['method']);
    }
    
    /**
     * @group integration
     */
    public function testShowReportNotFound() {
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
                putenv(trim($name) . '=' . trim($value));
            }
        }
        
        $controller = new WebController();
        $requestMock = $this->createMock(Request::class);
        
        // Use output buffering to catch the 404 HTML response
        ob_start();
        $responseMock = new class extends Response {
            public $statusCode = 200;
            public function setStatusCode(int $code) {
                $this->statusCode = $code;
            }
        };
        
        $controller->showReport($requestMock, $responseMock, "999999");
        $output = ob_get_clean();
        
        $this->assertEquals(404, $responseMock->statusCode);
        $this->assertStringContainsString('404 Not Found', $output);
    }
    
    /**
     * @group integration
     */
    public function testRenderUploadForm() {
        $controller = new WebController();
        $requestMock = $this->createMock(Request::class);
        $responseMock = new Response();
        
        ob_start();
        $controller->showUploadForm($requestMock, $responseMock);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('KontrakAman', $output);
        $this->assertStringContainsString('<form action="/upload" method="POST"', $output);
    }
    
    /**
     * @group integration
     */
    public function testRenderReportViewDirectly() {
        // Setup dummy data
        $contract = [
            'filename' => 'dummy.pdf',
            'contract_type' => 'PKWT',
            'uploaded_at' => '2026-09-24 10:00:00'
        ];
        $totalClauses = 1;
        $findings = [
            [
                'verdict' => 'violation',
                'severity' => 'high',
                'clause_number' => 'Pasal 1',
                'legal_basis' => 'Pasal X',
                'explanation' => 'Test',
                'clause_text' => 'Testing'
            ]
        ];
        
        ob_start();
        require __DIR__ . '/../app/Views/report.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('dummy.pdf', $output);
        $this->assertStringContainsString('PKWT', $output);
        $this->assertStringContainsString('Pasal 1', $output);
    }
}
