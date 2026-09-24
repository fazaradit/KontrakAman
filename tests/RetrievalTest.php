<?php

use PHPUnit\Framework\TestCase;
use App\Services\Retrieval\RegulationRepository;
use App\Services\Retrieval\HybridSearch;
use PHPUnit\Framework\Attributes\Group;

class RetrievalTest extends TestCase {
    
    public function testRegulationRepositoryWithMockData() {
        $pdo = \App\Config\Database::getConnection();
        
        $pdo->exec("DELETE FROM regulation_chunks WHERE source_law = 'UU TEST'");
        
        $vector1 = array_fill(0, 768, 0);
        $vector1[0] = 1.0;
        
        $vector2 = array_fill(0, 768, 0);
        $vector2[1] = 1.0;
        
        $v1Str = '[' . implode(',', $vector1) . ']';
        $v2Str = '[' . implode(',', $vector2) . ']';
        
        $pdo->exec("INSERT INTO regulation_chunks (source_law, pasal, full_text, topic_tags, embedding) VALUES ('UU TEST', 'Pasal 1', 'Masa percobaan test', ARRAY['masa_percobaan'], '$v1Str')");
        $pdo->exec("INSERT INTO regulation_chunks (source_law, pasal, full_text, topic_tags, embedding) VALUES ('UU TEST', 'Pasal 2', 'Cuti test', ARRAY['cuti'], '$v2Str')");
        
        $repo = new RegulationRepository($pdo);
        
        $results = $repo->findByTagsAndSimilarity('masa_percobaan', $vector1, 1);
        
        $this->assertCount(1, $results);
        $this->assertEquals('Pasal 1', $results[0]['pasal']);
        
        $results2 = $repo->findByTagsAndSimilarity('tag_tidak_ada', $vector2, 1);
        $this->assertCount(1, $results2);
        $this->assertEquals('Pasal 2', $results2[0]['pasal']);
        
        $pdo->exec("DELETE FROM regulation_chunks WHERE source_law = 'UU TEST'");
    }
    
    #[Group('integration')]
    public function testIntegrationHybridSearch() {
        if (class_exists(\Dotenv\Dotenv::class)) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->safeLoad();
        }
        
        $search = new HybridSearch();
        
        $clause = [
            'category' => 'masa_percobaan',
            'raw_text' => 'boleh gak PKWT ada masa percobaan'
        ];
        
        $results = $search->retrieve($clause);
        
        $this->assertNotEmpty($results);
        
        $topResult = $results[0];
        $this->assertStringContainsString('Pasal 58', $topResult['pasal']);
        $this->assertStringContainsString('UU 13/2003', $topResult['source_law']);
        
        echo "\nIntegration Test Top Result:\n";
        echo "Source: " . $topResult['source_law'] . " " . $topResult['pasal'] . "\n";
        echo "Text: " . $topResult['full_text'] . "\n";
        echo "Distance: " . $topResult['distance'] . "\n";
    }
}
