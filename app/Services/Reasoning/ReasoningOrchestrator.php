<?php
namespace App\Services\Reasoning;

class ReasoningOrchestrator {
    private $geminiClient;
    private $promptBuilder;
    private $responseParser;
    
    public function __construct(GeminiClient $geminiClient, PromptBuilder $promptBuilder, ResponseParser $responseParser) {
        $this->geminiClient = $geminiClient;
        $this->promptBuilder = $promptBuilder;
        $this->responseParser = $responseParser;
    }
    
    public function process(array $clause, array $retrievedRegulations): array {
        try {
            $prompt = $this->promptBuilder->build($clause, $retrievedRegulations);
            $rawResponse = $this->geminiClient->generate($prompt);
            
            if (preg_match('/```json\s*(.*?)\s*```/s', $rawResponse, $matches)) {
                $rawResponse = $matches[1];
            }
            
            $parsed = $this->responseParser->parse($rawResponse, $retrievedRegulations);
            
            return [
                'clause_id' => $clause['id'] ?? null,
                'matched_regulation_ids' => array_column($retrievedRegulations, 'id'),
                'verdict' => $parsed['verdict'],
                'severity' => $parsed['severity'],
                'source' => 'llm',
                'explanation' => $parsed['explanation_plain_language'],
                'confidence' => 0.85, 
                'raw_response' => $rawResponse
            ];
            
        } catch (GroundingViolationException $e) {
            return [
                'clause_id' => $clause['id'] ?? null,
                'matched_regulation_ids' => array_column($retrievedRegulations, 'id'),
                'verdict' => 'ambiguous',
                'severity' => 'low',
                'source' => 'llm',
                'explanation' => 'Gagal diverifikasi otomatis, perlu review manual',
                'confidence' => 0.0,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'clause_id' => $clause['id'] ?? null,
                'matched_regulation_ids' => array_column($retrievedRegulations, 'id'),
                'verdict' => 'ambiguous',
                'severity' => 'low',
                'source' => 'llm',
                'explanation' => 'Gagal dianalisis oleh AI, perlu review manual.',
                'confidence' => 0.0,
                'error' => $e->getMessage()
            ];
        }
    }
}
