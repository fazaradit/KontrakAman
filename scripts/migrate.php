<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

if (class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

try {
    $pdo = Database::getConnection();
    
    $migrationsDir = __DIR__ . '/../database/migrations';
    $files = glob($migrationsDir . '/*.sql');
    
    if ($files === false) {
        die("Error reading migrations directory.\n");
    }
    
    sort($files);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) PRIMARY KEY,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->query("SELECT migration FROM schema_migrations");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        if (in_array($filename, $executed)) {
            echo "- skipped: $filename (already migrated)\n";
            continue;
        }
        
        $sql = file_get_contents($file);
        
        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
            $stmt->execute([$filename]);
            echo "✓ migrated: $filename\n";
        } catch (PDOException $e) {
            die("Error migrating $filename: " . $e->getMessage() . "\n");
        }
    }
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
}
