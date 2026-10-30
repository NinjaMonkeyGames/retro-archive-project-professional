<?php

declare(strict_types=1);

/**
 * Executes db-data/schema.sql one statement at a time so any failure is
 * reported with the exact statement that caused it.
 *
 * @return array{success: bool, message: string}
 */
function runDatabaseMigration(PDO $pdo): array
{
    $schemaPath = __DIR__ . '/db-data/schema.sql';

    if (!file_exists($schemaPath)) {
        return ['success' => false, 'message' => "schema.sql not found at $schemaPath"];
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false || trim($sql) === '') {
        return ['success' => false, 'message' => 'schema.sql is empty or unreadable.'];
    }

    // Strip full-line "--" comments, then split on semicolons.
    $lines = array_filter(
        preg_split('/\R/', $sql),
        static fn(string $line): bool => !str_starts_with(ltrim($line), '--')
    );
    $statements = array_filter(
        array_map('trim', explode(';', implode("\n", $lines))),
        static fn(string $s): bool => $s !== ''
    );

    $executed = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 120);
            return [
                'success' => false,
                'message' => "Statement #" . ($executed + 1) . " failed: " . $e->getMessage()
                           . " | SQL: " . $preview . '...',
            ];
        }
    }

    return [
        'success' => true,
        'message' => "Database migrated and seeded from schema.sql ($executed statements executed).",
    ];
}