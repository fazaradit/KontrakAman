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
    
    foreach ($files as $file) {
        $filename = basename($file);
        $sql = file_get_contents($file);
        
        try {
            $pdo->exec($sql);
            echo "✓ migrated: $filename\n";
        } catch (PDOException $e) {
            die("Error migrating $filename: " . $e->getMessage() . "\n");
        }
    }
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
}
