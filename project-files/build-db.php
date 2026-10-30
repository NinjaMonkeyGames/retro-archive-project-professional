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

        // 7. Create Link Types Lookup Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS link_types (
                type_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type_code VARCHAR(50) NOT NULL UNIQUE,
                type_name VARCHAR(100) NOT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type_code (type_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 8. Create Affiliates Lookup Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS affiliates (
                affiliate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                affiliate_code VARCHAR(50) NOT NULL UNIQUE,
                affiliate_name VARCHAR(100) NOT NULL,
                base_url VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_affiliate_code (affiliate_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 9. Create Master Games Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                game_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                franchise_id BIGINT UNSIGNED DEFAULT NULL,
                origin_id BIGINT UNSIGNED DEFAULT NULL,
                media_id BIGINT UNSIGNED DEFAULT NULL,
                genre_id BIGINT UNSIGNED DEFAULT NULL,
                license_id BIGINT UNSIGNED DEFAULT NULL,
                status_id BIGINT UNSIGNED DEFAULT NULL,
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

        // 10. Create Fully Normalized Links Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS links (
                link_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                game_id BIGINT UNSIGNED NOT NULL,
                type_id BIGINT UNSIGNED NOT NULL,
                affiliate_id BIGINT UNSIGNED NOT NULL,
                url VARCHAR(1000) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_game_link_type_aff (game_id, type_id, affiliate_id, url(255)),
                FOREIGN KEY (game_id) 
                    REFERENCES games(game_id) ON DELETE CASCADE,
                FOREIGN KEY (type_id) 
                    REFERENCES link_types(type_id) ON DELETE RESTRICT,
                FOREIGN KEY (affiliate_id) 
                    REFERENCES affiliates(affiliate_id) ON DELETE RESTRICT,
                INDEX idx_game_links (game_id),
                INDEX idx_type (type_id),
                INDEX idx_affiliate (affiliate_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci;
        ");

        // 11. Ingest Origins
        $stmtOrigin = $pdo->prepare("
            INSERT INTO origins (origin_id, origin_code, origin_name, flag_link)
            VALUES (:origin_id, :origin_code, :origin_name, :flag_link)
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

        // 12. Ingest Franchises
        $stmtFranchise = $pdo->prepare("
            INSERT INTO franchises (franchise_id, franchise_name, original_creator, inception_year, notes)
            VALUES (:franchise_id, :franchise_name, :original_creator, :inception_year, :notes)
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

        // 13. Ingest Media Types
        $mediaTypes = $jsonData['media_types'] ?? [
            ['media_id' => 1, 'media_code' => 'CARTRIDGE', 'media_name' => 'ROM Cartridge', 'notes' => 'Solid-state board'],
            ['media_id' => 2, 'media_code' => 'TAPE', 'media_name' => 'Magnetic Tape', 'notes' => 'Cassette tape'],
            ['media_id' => 3, 'media_code' => 'CDROM', 'media_name' => 'CD-ROM', 'notes' => 'Optical disc']
        ];
        $stmtMedia = $pdo->prepare("
            INSERT INTO media_types (media_id, media_code, media_name, notes)
            VALUES (:media_id, :media_code, :media_name, :notes)
            ON DUPLICATE KEY UPDATE media_name = VALUES(media_name)
        ");
        foreach ($mediaTypes as $media) {
            $stmtMedia->execute([
                'media_id'   => $media['media_id'] ?? null,
                'media_code' => $media['media_code'] ?? 'UNKNOWN',
                'media_name' => $media['media_name'] ?? 'Unknown Media',
                'notes'      => $media['notes'] ?? null,
            ]);
        }

        // 14. Ingest Link Types Lookup
        $linkTypes = $jsonData['link_types'] ?? [
            ['type_id' => 1, 'type_code' => 'TROPHIES', 'type_name' => 'Trophies / Achievements'],
            ['type_id' => 2, 'type_code' => 'MANUALS', 'type_name' => 'Documentation & Manuals'],
            ['type_id' => 3, 'type_code' => 'ROMS', 'type_name' => 'ROMs & Images'],
            ['type_id' => 4, 'type_code' => 'CHEATS', 'type_name' => 'Cheats & Strategy'],
            ['type_id' => 5, 'type_code' => 'DATABASE', 'type_name' => 'Archival Database']
        ];
        $stmtLinkType = $pdo->prepare("
            INSERT INTO link_types (type_id, type_code, type_name, notes)
            VALUES (:type_id, :type_code, :type_name, :notes)
            ON DUPLICATE KEY UPDATE type_name = VALUES(type_name)
        ");
        foreach ($linkTypes as $lt) {
            $stmtLinkType->execute([
                'type_id'   => $lt['type_id'] ?? null,
                'type_code' => $lt['type_code'] ?? '',
                'type_name' => $lt['type_name'] ?? '',
                'notes'     => $lt['notes'] ?? null,
            ]);
        }

        // 15. Ingest Affiliates Lookup
        $affiliates = $jsonData['affiliates'] ?? [
            ['affiliate_id' => 1, 'affiliate_code' => 'MOBYGAMES', 'affiliate_name' => 'MobyGames'],
            ['affiliate_id' => 2, 'affiliate_code' => 'RETROACHIEVEMENTS', 'affiliate_name' => 'RetroAchievements'],
            ['affiliate_id' => 3, 'affiliate_code' => 'SERIAL_STATION', 'affiliate_name' => 'Serial Station'],
            ['affiliate_id' => 4, 'affiliate_code' => 'GAMEFAQS', 'affiliate_name' => 'GameFAQs']
        ];
        $stmtAff = $pdo->prepare("
            INSERT INTO affiliates (affiliate_id, affiliate_code, affiliate_name, base_url)
            VALUES (:affiliate_id, :affiliate_code, :affiliate_name, :base_url)
            ON DUPLICATE KEY UPDATE affiliate_name = VALUES(affiliate_name)
        ");
        foreach ($affiliates as $aff) {
            $stmtAff->execute([
                'affiliate_id'   => $aff['affiliate_id'] ?? null,
                'affiliate_code' => $aff['affiliate_code'] ?? '',
                'affiliate_name' => $aff['affiliate_name'] ?? '',
                'base_url'       => $aff['base_url'] ?? null,
            ]);
        }

        // 16. Ingest Genres
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

        // 17. Ingest Licenses
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

        // 18. Ingest Statuses
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

        // 19. Ingest Games & Links with Domain-Based Affiliate Guard
        $stmtGame = $pdo->prepare("
            INSERT INTO games (
                game_id, franchise_id, origin_id, media_id, genre_id, 
                license_id, status_id, canonical_title, 
                first_release_year, data_source, notes
            ) VALUES (
                :game_id, :franchise_id, :origin_id, :media_id, :genre_id, 
                :license_id, :status_id, :canonical_title, 
                :first_release_year, :data_source, :notes
            )
            ON DUPLICATE KEY UPDATE canonical_title = VALUES(canonical_title)
        ");

        $stmtLink = $pdo->prepare("
            INSERT INTO links (game_id, type_id, affiliate_id, url)
            VALUES (:game_id, :type_id, :affiliate_id, :url)
            ON DUPLICATE KEY UPDATE url = VALUES(url)
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
                'canonical_title'     => $game['canonical_title'] ?? '',
                'first_release_year'  => $game['first_release_year'] ?? null,
                'data_source'         => $game['data_source'] ?? 'manual',
                'notes'               => $game['notes'] ?? null,
            ]);

            $gameId = $game['game_id'] ?? $pdo->lastInsertId();

            if (!empty($game['links']) && is_array($game['links'])) {
                foreach ($game['links'] as $link) {
                    $url = $link['url'] ?? '';
                    $host = parse_url($url, PHP_URL_HOST) ?? '';

                    // Enforce correct affiliate based on domain to prevent cross-contamination
                    $affiliateId = $link['affiliate_id'] ?? 1;
                    if (str_contains($host, 'retroachievements.org')) {
                        $affiliateId = 2; // RetroAchievements
                    } elseif (str_contains($host, 'serialstation.com')) {
                        $affiliateId = 3; // Serial Station
                    } elseif (str_contains($host, 'mobygames.com')) {
                        $affiliateId = 1; // MobyGames
                    }

                    $stmtLink->execute([
                        'game_id'      => $gameId,
                        'type_id'      => $link['type_id'] ?? 5,
                        'affiliate_id' => $affiliateId,
                        'url'          => $url,
                    ]);
                }
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
