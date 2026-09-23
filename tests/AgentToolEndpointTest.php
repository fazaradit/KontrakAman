<?php
use PHPUnit\Framework\TestCase;
use App\Services\HermesGateway\AgentToolEndpoint;
use App\Core\Request;
use App\Core\Response;

class AgentToolEndpointTest extends TestCase {
    public function testEmptyInputReturnsError() {
        $endpoint = new AgentToolEndpoint();
        
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('getBody')->willReturn([]);
        $requestMock->method('getFiles')->willReturn([]);
        
        $responseMock = $this->createMock(Response::class);
        $responseMock->expects($this->once())
            ->method('json')
            ->with($this->arrayHasKey('error'), 400);
            
        $endpoint->handle($requestMock, $responseMock);
    }
    
    public function testInvalidFileTypeReturnsError() {
        $endpoint = new AgentToolEndpoint();
        
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('getBody')->willReturn([]);
        $requestMock->method('getFiles')->willReturn([
            'file' => [
                'error' => UPLOAD_ERR_OK,
                'type' => 'image/png', // Invalid type
                'size' => 1024,
                'tmp_name' => '/tmp/dummy.png',
                'name' => 'dummy.png'
            ]
        ]);
        
        $responseMock = $this->createMock(Response::class);
        $responseMock->expects($this->once())
            ->method('json')
            ->with($this->arrayHasKey('error'), 400);
            
        $endpoint->handle($requestMock, $responseMock);
    }
}
