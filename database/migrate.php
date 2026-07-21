<?php
// database/migrate.php

require_once __DIR__ . '/../config/database.php';

echo "Starting Database Migration Runner...\n";

try {
    $pdo = Database::getConnection();

    // Ensure schema_migrations table exists first
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // Fetch executed migrations
    $stmt = $pdo->query("SELECT migration FROM schema_migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    $newMigrations = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $executedMigrations, true)) {
            echo "Executing migration: $filename ... ";
            $sql = file_get_contents($file);
            $pdo->exec($sql);

            $ins = $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
            $ins->execute([$filename]);

            echo "SUCCESS\n";
            $newMigrations++;
        }
    }

    if ($newMigrations === 0) {
        echo "Database is already up to date.\n";
    } else {
        echo "Successfully executed $newMigrations migration(s).\n";
    }

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
