<?php
namespace App\Services\Retrieval;

use App\Config\Database;
use PDO;

class RegulationRepository {
    private PDO $pdo;
    
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getConnection();
    }
    
    public function findByTagsAndSimilarity(string $category, array $queryEmbedding, int $limit = 5): array {
        $vectorStr = '[' . implode(',', $queryEmbedding) . ']';
        
        $sql = "
            SELECT pasal, ayat, source_law, full_text, embedding <=> :embedding AS distance
            FROM regulation_chunks
            WHERE topic_tags @> ARRAY[:category]::text[]
            ORDER BY embedding <=> :embedding
            LIMIT :limit
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':embedding', $vectorStr);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) === 0) {
            $sql = "
                SELECT pasal, ayat, source_law, full_text, embedding <=> :embedding AS distance
                FROM regulation_chunks
                ORDER BY embedding <=> :embedding
                LIMIT :limit
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':embedding', $vectorStr);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $results;
    }
}
