<?php

declare(strict_types=1);

/**
 * Executes the standalone schema.sql database build script from db-data.
 * 
 * @param PDO $pdo Active database connection
 * @return bool True on success, false on failure
 */
function runDatabaseMigration(PDO $pdo): bool {
    $schemaPath = __DIR__ . '/db-data/schema.sql';

    if (!file_exists($schemaPath)) {
        return false;
    }

    $sql = file_get_contents($schemaPath);

    if ($sql === false || trim($sql) === '') {
        return false;
    }

    try {
        // Execute multi-query SQL schema file directly
        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        // Uncomment during debugging if an exception occurs:
        // echo "SQL Error: " . $e->getMessage();
        return false;
    }
}