<?php

declare(strict_types=1);

/**
 * Executes the database migration and seeding process inline from JSON.
 *
 * @param PDO $pdo Active database connection
 * @return bool True on success, false on failure
 */
function runDatabaseMigration(PDO $pdo): bool
{
    $dbDataPath = __DIR__ . '/db-data/game-data.json';

    if (!file_exists($dbDataPath)) {
        return false;
    }

    $jsonRaw = file_get_contents($dbDataPath);
    $jsonData = json_decode($jsonRaw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    try {
        // 1. Create Origins Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS origins (
                origin_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                origin_code VARCHAR(10) NOT NULL UNIQUE,
                origin_name VARCHAR(255) NOT NULL,
                flag_link VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_origin_code (origin_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Create Franchises Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS franchises (
                franchise_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                franchise_name VARCHAR(255) NOT NULL UNIQUE,
                original_creator VARCHAR(255) DEFAULT NULL,
                inception_year SMALLINT UNSIGNED,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_franchise_name (franchise_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Create Media Types Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS media_types (
                media_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                media_code VARCHAR(50) NOT NULL UNIQUE,
                media_name VARCHAR(100) NOT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_media_code (media_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Create Genres Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS genres (
                genre_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                genre_name VARCHAR(100) NOT NULL UNIQUE,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_genre_name (genre_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Create Licenses Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                license_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                license_code VARCHAR(50) NOT NULL UNIQUE,
                license_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_license_code (license_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Create Statuses Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS statuses (
                status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                status_code VARCHAR(50) NOT NULL UNIQUE,
                status_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status_code (status_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. Create Master Games Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                game_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                franchise_id BIGINT UNSIGNED DEFAULT NULL,
                origin_id BIGINT UNSIGNED DEFAULT NULL,
                media_id BIGINT UNSIGNED DEFAULT NULL,
                genre_id BIGINT UNSIGNED DEFAULT NULL,
                license_id BIGINT UNSIGNED DEFAULT NULL,
                status_id BIGINT UNSIGNED DEFAULT NULL,
                wikidata_qid VARCHAR(20) UNIQUE DEFAULT NULL,
                canonical_title VARCHAR(255) NOT NULL,
                first_release_year SMALLINT UNSIGNED,
                data_source VARCHAR(100) DEFAULT 'manual',
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                           ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (franchise_id) 
                    REFERENCES franchises(franchise_id) ON DELETE SET NULL,
                FOREIGN KEY (origin_id) 
                    REFERENCES origins(origin_id) ON DELETE SET NULL,
                FOREIGN KEY (media_id) 
                    REFERENCES media_types(media_id) ON DELETE SET NULL,
                FOREIGN KEY (genre_id) 
                    REFERENCES genres(genre_id) ON DELETE SET NULL,
                FOREIGN KEY (license_id) 
                    REFERENCES licenses(license_id) ON DELETE SET NULL,
                FOREIGN KEY (status_id) 
                    REFERENCES statuses(status_id) ON DELETE SET NULL,
                INDEX idx_wikidata (wikidata_qid),
                INDEX idx_canonical_title (canonical_title),
                INDEX idx_franchise (franchise_id),
                INDEX idx_origin (origin_id),
                INDEX idx_media (media_id),
                INDEX idx_genre (genre_id),
                INDEX idx_license (license_id),
                INDEX idx_status (status_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 8. Safety checks for existing columns/tables
        $colCheckMedia = $pdo->query(
            "SHOW COLUMNS FROM games LIKE 'media_id'"
        );
        if ($colCheckMedia->rowCount() === 0) {
            $pdo->exec("
                ALTER TABLE games 
                ADD COLUMN media_id BIGINT UNSIGNED DEFAULT NULL 
                AFTER origin_id
            ");
            $pdo->exec("ALTER TABLE games ADD INDEX idx_media (media_id)");
        }

        // 9. Ingest Origins
        $stmtOrigin = $pdo->prepare("
            INSERT INTO origins (origin_id, origin_code, 
                                 origin_name, flag_link)
            VALUES (:origin_id, :origin_code, 
                    :origin_name, :flag_link)
            ON DUPLICATE KEY UPDATE origin_name = VALUES(origin_name)
        ");
        foreach ($jsonData['origins'] ?? [] as $origin) {
            $stmtOrigin->execute([
                'origin_id'   => $origin['origin_id'] ?? null,
                'origin_code' => $origin['origin_code'] ?? '',
                'origin_name' => $origin['origin_name'] ?? '',
                'flag_link'   => $origin['flag_link'] ?? null,
            ]);
        }

        // 10. Ingest Franchises
        $stmtFranchise = $pdo->prepare("
            INSERT INTO franchises (franchise_id, franchise_name, 
                                    original_creator, inception_year, notes)
            VALUES (:franchise_id, :franchise_name, 
                    :original_creator, :inception_year, :notes)
            ON DUPLICATE KEY UPDATE franchise_name = VALUES(franchise_name)
        ");
        foreach ($jsonData['franchises'] ?? [] as $franchise) {
            $stmtFranchise->execute([
                'franchise_id'     => $franchise['franchise_id'] ?? null,
                'franchise_name'   => $franchise['franchise_name'] ?? '',
                'original_creator' => $franchise['original_creator'] ?? null,
                'inception_year'   => $franchise['inception_year'] ?? null,
                'notes'            => $franchise['notes'] ?? null,
            ]);
        }

        // 11. Ingest Media Types (with fallback default if empty)
        $mediaTypes = $jsonData['media_types'] ?? [];
        if (empty($mediaTypes)) {
            $mediaTypes = [[
                'media_id'   => 1,
                'media_code' => 'PENDING',
                'media_name' => 'Unspecified Media',
                'notes'      => 'Default fallback media type'
            ]];
        }

        $stmtMedia = $pdo->prepare("
            INSERT INTO media_types (media_id, media_code, media_name, notes)
            VALUES (:media_id, :media_code, :media_name, :notes)
            ON DUPLICATE KEY UPDATE media_name = VALUES(media_name)
        ");
        foreach ($mediaTypes as $media) {
            $stmtMedia->execute([
                'media_id'   => $media['media_id'] ?? null,
                'media_code' => $media['media_code'] ?? 'PENDING',
                'media_name' => $media['media_name'] ?? 'Unspecified Media',
                'notes'      => $media['notes'] ?? null,
            ]);
        }

        // 12. Ingest Genres
        $stmtGenre = $pdo->prepare("
            INSERT INTO genres (genre_id, genre_name, notes)
            VALUES (:genre_id, :genre_name, :notes)
            ON DUPLICATE KEY UPDATE genre_name = VALUES(genre_name)
        ");
        foreach ($jsonData['genres'] ?? [] as $genre) {
            $stmtGenre->execute([
                'genre_id'   => $genre['genre_id'] ?? null,
                'genre_name' => $genre['genre_name'] ?? '',
                'notes'      => $genre['notes'] ?? null,
            ]);
        }

        // 13. Ingest Licenses
        $stmtLicense = $pdo->prepare("
            INSERT INTO licenses (license_id, license_code, license_name)
            VALUES (:license_id, :license_code, :license_name)
            ON DUPLICATE KEY UPDATE license_name = VALUES(license_name)
        ");
        foreach ($jsonData['licenses'] ?? [] as $license) {
            $stmtLicense->execute([
                'license_id'   => $license['license_id'] ?? null,
                'license_code' => $license['license_code'] ?? '',
                'license_name' => $license['license_name'] ?? '',
            ]);
        }

        // 14. Ingest Statuses
        $stmtStatus = $pdo->prepare("
            INSERT INTO statuses (status_id, status_code, status_name)
            VALUES (:status_id, :status_code, :status_name)
            ON DUPLICATE KEY UPDATE status_name = VALUES(status_name)
        ");
        foreach ($jsonData['statuses'] ?? [] as $status) {
            $stmtStatus->execute([
                'status_id'   => $status['status_id'] ?? null,
                'status_code' => $status['status_code'] ?? '',
                'status_name' => $status['status_name'] ?? '',
            ]);
        }

        // 15. Ingest Games
        $stmtGame = $pdo->prepare("
            INSERT INTO games (
                game_id, franchise_id, origin_id, media_id, genre_id, 
                license_id, status_id, wikidata_qid, canonical_title, 
                first_release_year, data_source, notes
            ) VALUES (
                :game_id, :franchise_id, :origin_id, :media_id, :genre_id, 
                :license_id, :status_id, :wikidata_qid, :canonical_title, 
                :first_release_year, :data_source, :notes
            )
            ON DUPLICATE KEY UPDATE canonical_title = VALUES(canonical_title)
        ");
        foreach ($jsonData['games'] ?? [] as $game) {
            $stmtGame->execute([
                'game_id'             => $game['game_id'] ?? null,
                'franchise_id'        => $game['franchise_id'] ?? null,
                'origin_id'           => $game['origin_id'] ?? null,
                'media_id'            => $game['media_id'] ?? null,
                'genre_id'            => $game['genre_id'] ?? null,
                'license_id'          => $game['license_id'] ?? null,
                'status_id'           => $game['status_id'] ?? null,
                'wikidata_qid'        => $game['wikidata_qid'] ?? null,
                'canonical_title'     => $game['canonical_title'] ?? '',
                'first_release_year'  => $game['first_release_year'] ?? null,
                'data_source'         => $game['data_source'] ?? 'manual',
                'notes'               => $game['notes'] ?? null,
            ]);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Exports current database records and triggers a browser file download.
 *
 * @param PDO $pdo Active database connection
 * @return void
 */
function downloadDatabaseFile(PDO $pdo): void
{
    try {
        $exportData = [
            'franchises'  => $pdo->query(
                "SELECT * FROM franchises ORDER BY franchise_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'media_types' => $pdo->query(
                "SELECT * FROM media_types ORDER BY media_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'genres'      => $pdo->query(
                "SELECT * FROM genres ORDER BY genre_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'licenses'    => $pdo->query(
                "SELECT * FROM licenses ORDER BY license_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'origins'     => $pdo->query(
                "SELECT * FROM origins ORDER BY origin_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'statuses'    => $pdo->query(
                "SELECT * FROM statuses ORDER BY status_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
            'games'       => $pdo->query(
                "SELECT * FROM games ORDER BY game_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC),
        ];

        $jsonEncoded = json_encode(
            $exportData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        if ($jsonEncoded === false) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Failed to encode database JSON.";
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="game-data.json"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($jsonEncoded));

        echo $jsonEncoded;
        exit;
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h1>Export Failed</h1><p>Database tables have not " .
             "been created yet. Please click <strong>Run DB Build & " .
             "Migration</strong> first.</p>" .
             "<p><a href='index.php'>&larr; Back to Catalog</a></p>";
        exit;
    }
}
