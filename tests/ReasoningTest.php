<?php

use PHPUnit\Framework\TestCase;
use App\Services\Reasoning\PromptBuilder;
use App\Services\Reasoning\ResponseParser;
use App\Services\Reasoning\ReasoningOrchestrator;
use App\Services\Reasoning\GeminiClient;
use App\Services\Reasoning\GroundingViolationException;
use App\Services\Reasoning\ResponseParsingException;

class ReasoningTest extends TestCase {
    
    public function testPromptBuilder() {
        $builder = new PromptBuilder();
        $clause = ['raw_text' => 'Masa percobaan 6 bulan', 'category' => 'masa_percobaan'];
        $regs = [
            ['source_law' => 'UU 13/2003', 'pasal' => 'Pasal 58', 'ayat' => '', 'full_text' => 'PKWT tidak dapat mensyaratkan masa percobaan kerja.']
        ];
        
        $prompt = $builder->build($clause, $regs);
        $this->assertStringContainsString('Masa percobaan 6 bulan', $prompt);
        $this->assertStringContainsString('masa_percobaan', $prompt);
        $this->assertStringContainsString('Sumber: UU 13/2003', $prompt);
        $this->assertStringContainsString('Pasal: Pasal 58', $prompt);
    }
    
    public function testResponseParserValidGrounding() {
        $parser = new ResponseParser();
        $regs = [
            ['source_law' => 'UU 13/2003', 'pasal' => 'Pasal 58', 'ayat' => '']
        ];
        $json = '{
            "verdict": "violation",
            "cited_pasal": "Pasal 58",
            "cited_source_law": "UU 13/2003",
            "explanation_plain_language": "Tidak boleh ada masa percobaan.",
            "severity": "high"
        }';
        
        $result = $parser->parse($json, $regs);
        $this->assertEquals('violation', $result['verdict']);
    }
    
    public function testResponseParserMissingFieldThrowsException() {
        $parser = new ResponseParser();
        $json = '{
            "verdict": "violation"
        }';
        $this->expectException(ResponseParsingException::class);
        $parser->parse($json, []);
    }
    
    public function testResponseParserInvalidJsonThrowsException() {
        $parser = new ResponseParser();
        $json = 'bukan json';
        $this->expectException(ResponseParsingException::class);
        $parser->parse($json, []);
    }
    
    public function testResponseParserGroundingViolationThrowsException() {
        $parser = new ResponseParser();
        $regs = [
            ['source_law' => 'UU 13/2003', 'pasal' => 'Pasal 58', 'ayat' => '']
        ];
        $json = '{
            "verdict": "violation",
            "cited_pasal": "Pasal 60",
            "cited_source_law": "UU 13/2003",
            "explanation_plain_language": "Tidak boleh ada masa percobaan.",
            "severity": "high"
        }';
        $this->expectException(GroundingViolationException::class);
        $parser->parse($json, $regs);
    }
    
    /**
     * @group integration
     */
    public function testIntegrationReasoningOrchestrator() {
        $client = new GeminiClient();
        $builder = new PromptBuilder();
        $parser = new ResponseParser();
        $orchestrator = new ReasoningOrchestrator($client, $builder, $parser);
        
        $clause = [
            'id' => 1,
            'raw_text' => 'Pihak kedua bersedia menjalani masa percobaan selama 3 bulan sejak ditandatanganinya PKWT ini.',
            'category' => 'masa_percobaan'
        ];
        $regs = [
            [
                'id' => 101,
                'source_law' => 'UU 13/2003',
                'pasal' => 'Pasal 58',
                'ayat' => '',
                'full_text' => '(1) Perjanjian kerja untuk waktu tertentu tidak dapat mensyaratkan adanya masa percobaan kerja. (2) Dalam hal disyaratkan masa percobaan kerja dalam perjanjian kerja sebagaimana dimaksud dalam ayat (1), masa percobaan kerja yang disyaratkan batal demi hukum dan masa kerja tetap dihitung.'
            ]
        ];
        
        $result = $orchestrator->process($clause, $regs);
        
        echo "\n[INTEGRATION TEST RESULT]\n";
        print_r($result);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('verdict', $result);
    }
}
