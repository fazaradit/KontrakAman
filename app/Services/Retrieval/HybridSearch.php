<?php
namespace App\Services\Retrieval;

class HybridSearch {
    private EmbeddingClient $embeddingClient;
    private RegulationRepository $repository;
    
    public function __construct() {
        $this->embeddingClient = new EmbeddingClient();
        $this->repository = new RegulationRepository();
    }
    
    public function retrieve(array $clause): array {
        $category = $clause['category'];
        $text = $clause['raw_text'];
        
        $embedding = $this->embeddingClient->embed($text);
        
        return $this->repository->findByTagsAndSimilarity($category, $embedding);
    }
}
