<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Retrieval\EmbeddingClient;
use App\Config\Database;
use Dotenv\Dotenv;

if (class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$pdo = Database::getConnection();
$embeddingClient = new EmbeddingClient();

$pdo->exec("TRUNCATE TABLE regulation_chunks");

$seedDir = __DIR__ . '/../database/seeders/regulations_source';
$files = glob($seedDir . '/*.txt');

$totalInserted = 0;

foreach ($files as $file) {
    $category = pathinfo($file, PATHINFO_FILENAME);
    $content = file_get_contents($file);
    
    $blocks = explode('SOURCE:', $content);
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;
        
        $lines = explode("\n", $block);
        $sourceLaw = trim($lines[0]);
        $pasal = trim($lines[1]);
        
        $ayatText = implode("\n", array_slice($lines, 2));
        
        preg_match_all('/^\s*\(\d+\).*?(?=(^\s*\(\d+\)|\z))/ms', $ayatText, $ayatMatches);
        
        if (empty($ayatMatches[0])) {
            insertChunk($pdo, $embeddingClient, $sourceLaw, $pasal, null, $ayatText, $category);
            $totalInserted++;
        } else {
            foreach ($ayatMatches[0] as $ayatStr) {
                $ayatStr = trim($ayatStr);
                preg_match('/^\s*\((\d+)\)/', $ayatStr, $ayatNumMatch);
                $ayat = isset($ayatNumMatch[1]) ? $ayatNumMatch[1] : null;
                
                insertChunk($pdo, $embeddingClient, $sourceLaw, $pasal, $ayat, $ayatStr, $category);
                $totalInserted++;
            }
        }
        
        // to avoid rate limit easily, sleep 1s
        sleep(1);
    }
}

echo "Total chunks ingested: $totalInserted\n";

function insertChunk($pdo, $client, $source, $pasal, $ayat, $text, $category) {
    $text = trim($text);
    if (empty($text)) return;
    
    $vector = $client->embed($text);
    $vectorStr = '[' . implode(',', $vector) . ']';
    
    $stmt = $pdo->prepare("INSERT INTO regulation_chunks (source_law, pasal, ayat, full_text, topic_tags, embedding) VALUES (?, ?, ?, ?, ARRAY[?], ?)");
    $stmt->execute([
        $source,
        $pasal,
        $ayat,
        $text,
        $category,
        $vectorStr
    ]);
    
    echo "✓ ingested: $source $pasal" . ($ayat ? " ayat ($ayat)" : "") . "\n";
}
