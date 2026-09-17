<?php
namespace App\Services\Retrieval;

use App\Config\Gemini;

class EmbeddingClient {
    public function embed(string $text): array {
        $apiKey = Gemini::getApiKey();
        if (empty($apiKey)) {
            throw new EmbeddingException("API key Gemini tidak diset.");
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key=' . $apiKey;
        
        $data = [
            'model' => 'models/gemini-embedding-001',
            'outputDimensionality' => 768,
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        
        $maxRetries = 2;
        $attempt = 0;
        
        while ($attempt <= $maxRetries) {
            $result = file_get_contents($url, false, $context);
            $statusCode = $this->getHttpResponseCode($http_response_header ?? []);
            
            if ($statusCode === 429) {
                $attempt++;
                if ($attempt <= $maxRetries) {
                    sleep(2);
                    continue;
                }
            }
            
            if ($statusCode !== 200) {
                throw new EmbeddingException("Gagal memanggil Gemini API. Status HTTP: $statusCode. Respon: " . ($result ?: 'none'));
            }
            
            if ($result === false) {
                throw new EmbeddingException("Gagal melakukan network request ke Gemini API.");
            }
            
            $json = json_decode($result, true);
            if (!isset($json['embedding']['values'])) {
                throw new EmbeddingException("Respons dari Gemini API tidak sesuai format yang diharapkan.");
            }
            
            return $json['embedding']['values'];
        }
        
        throw new EmbeddingException("Gagal setelah retry.");
    }
    
    private function getHttpResponseCode(array $headers): int {
        if (empty($headers)) return 0;
        if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $headers[0], $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
}
