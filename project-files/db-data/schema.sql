-- -----------------------------------------------------------------------------
-- Retro Gaming Archive - Master IP Catalog Schema with Native Comments
-- -----------------------------------------------------------------------------

SET foreign_key_checks = 0;

DROP TABLE IF EXISTS links;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS platforms;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS franchises;
DROP TABLE IF EXISTS studios;
DROP TABLE IF EXISTS media_types;
DROP TABLE IF EXISTS genres;
DROP TABLE IF EXISTS licenses;
DROP TABLE IF EXISTS statuses;
DROP TABLE IF EXISTS link_types;
DROP TABLE IF EXISTS affiliates;
DROP TABLE IF EXISTS origins;

SET foreign_key_checks = 1;

-- 1. Origins Table
CREATE TABLE origins (
    origin_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for country/territory origin',
    origin_code VARCHAR(10) NOT NULL UNIQUE 
        COMMENT 'ISO Alpha-3 country code (e.g. JPN, GBR)',
    origin_name VARCHAR(255) NOT NULL 
        COMMENT 'Full localized country or region name',
    flag_link VARCHAR(500) DEFAULT NULL 
        COMMENT 'URL to territorial flag icon asset',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when origin record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Vendors Table
CREATE TABLE vendors (
    vendor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for hardware manufacturer',
    vendor_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short machine-readable vendor code (e.g. SEGA)',
    vendor_name VARCHAR(255) NOT NULL 
        COMMENT 'Official corporate vendor name',
    country_code VARCHAR(10) DEFAULT NULL 
        COMMENT 'HQ country origin code',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when vendor record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Media Types Table
CREATE TABLE media_types (
    media_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for physical storage format',
    media_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short code for media format (e.g. CARTRIDGE, CDROM)',
    media_name VARCHAR(100) NOT NULL 
        COMMENT 'Human-readable media name',
    notes TEXT DEFAULT NULL 
        COMMENT 'Technical hardware medium details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when media type record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Platforms Table
CREATE TABLE platforms (
    platform_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for console hardware platform',
    vendor_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking platform to its hardware vendor',
    media_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking platform to its native storage media',
    platform_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short code for console (e.g. GENESIS, PSX)',
    platform_name VARCHAR(100) NOT NULL 
        COMMENT 'Official console market name',
    generation TINYINT UNSIGNED DEFAULT NULL 
        COMMENT 'Console hardware bit/generation classification',
    release_year SMALLINT UNSIGNED DEFAULT NULL 
        COMMENT 'Original hardware commercial launch year',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when platform record was created',
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE SET NULL,
    FOREIGN KEY (media_id) REFERENCES media_types(media_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Studios Table
CREATE TABLE studios (
    studio_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for game development studio',
    studio_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short code for development team',
    studio_name VARCHAR(255) NOT NULL 
        COMMENT 'Official studio or team name',
    country_code VARCHAR(10) DEFAULT NULL 
        COMMENT 'Studio country origin',
    founded_year SMALLINT UNSIGNED DEFAULT NULL 
        COMMENT 'Year studio was established',
    notes TEXT DEFAULT NULL 
        COMMENT 'Historical studio background notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when studio record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Franchises Table
CREATE TABLE franchises (
    franchise_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for intellectual property franchise',
    studio_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking franchise to primary creator studio',
    franchise_name VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Official IP franchise title',
    inception_year SMALLINT UNSIGNED 
        COMMENT 'Year the franchise debuted',
    notes TEXT DEFAULT NULL 
        COMMENT 'Franchise background description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when franchise record was created',
    FOREIGN KEY (studio_id) REFERENCES studios(studio_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Genres Table
CREATE TABLE genres (
    genre_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for game genre category',
    genre_name VARCHAR(100) NOT NULL UNIQUE 
        COMMENT 'Official genre classification name',
    notes TEXT DEFAULT NULL 
        COMMENT 'Genre gameplay mechanics description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when genre record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Licenses Table
CREATE TABLE licenses (
    license_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for IP licensing model',
    license_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short license code',
    license_name VARCHAR(100) NOT NULL 
        COMMENT 'Full legal license description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when license record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Statuses Table
CREATE TABLE statuses (
    status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for archival preservation status',
    status_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short status code',
    status_name VARCHAR(100) NOT NULL 
        COMMENT 'Detailed archival verification status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when status record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Link Types Lookup Table
CREATE TABLE link_types (
    type_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for external reference category',
    type_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short link category code (e.g. TROPHIES, DATABASE)',
    type_name VARCHAR(100) NOT NULL 
        COMMENT 'Human-readable reference category name',
    notes TEXT DEFAULT NULL 
        COMMENT 'Description of what this external link contains',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when link type record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Affiliates Lookup Table
CREATE TABLE affiliates (
    affiliate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate key for external archival affiliate partner',
    affiliate_code VARCHAR(50) NOT NULL UNIQUE 
        COMMENT 'Short partner code (e.g. MOBYGAMES)',
    affiliate_name VARCHAR(100) NOT NULL 
        COMMENT 'Official partner service name',
    base_url VARCHAR(500) DEFAULT NULL 
        COMMENT 'Base web domain URL for the affiliate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when affiliate record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Master Games Table
CREATE TABLE games (
    game_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate primary key for master IP catalog entry',
    franchise_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its parent IP franchise',
    origin_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its country of origin',
    platform_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its primary target platform',
    genre_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its core gameplay genre',
    license_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its legal copyright status',
    status_id BIGINT UNSIGNED DEFAULT NULL 
        COMMENT 'Foreign key linking title to its archival verification status',
    canonical_title VARCHAR(255) NOT NULL 
        COMMENT 'Standardized English release title of the game',
    first_release_year SMALLINT UNSIGNED 
        COMMENT 'Chronological year of the earliest worldwide release',
    data_source VARCHAR(100) DEFAULT 'manual' 
        COMMENT 'Data ingestion origin (e.g. manual, api, scraper)',
    notes TEXT DEFAULT NULL 
        COMMENT 'Curatorial archival notes and preservation remarks',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when master game record was created',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when master game record was last updated',
    FOREIGN KEY (franchise_id) REFERENCES franchises(franchise_id) ON DELETE SET NULL,
    FOREIGN KEY (origin_id) REFERENCES origins(origin_id) ON DELETE SET NULL,
    FOREIGN KEY (platform_id) REFERENCES platforms(platform_id) ON DELETE SET NULL,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE SET NULL,
    FOREIGN KEY (license_id) REFERENCES licenses(license_id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES statuses(status_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Normalized Links Table
CREATE TABLE links (
    link_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Surrogate primary key for external archival reference',
    game_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Foreign key linking reference to master game record',
    type_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Foreign key linking reference to category',
    affiliate_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Foreign key linking reference to partner database',
    url VARCHAR(1000) NOT NULL 
        COMMENT 'Exact target URL pointing to external resource',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Timestamp when link reference record was created',
    UNIQUE KEY uk_game_link_type_aff (game_id, type_id, affiliate_id, url(255)),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES link_types(type_id) ON DELETE RESTRICT,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(affiliate_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Initial Seed Data
-- -----------------------------------------------------------------------------
INSERT INTO origins (origin_id, origin_code, origin_name) VALUES (1, 'JPN', 'Japan');
INSERT INTO vendors (vendor_id, vendor_code, vendor_name) VALUES (1, 'SEGA', 'Sega Enterprises, Ltd.');
INSERT INTO media_types (media_id, media_code, media_name) VALUES (1, 'CARTRIDGE', 'ROM Cartridge');
INSERT INTO platforms (platform_id, vendor_id, media_id, platform_code, platform_name) 
VALUES (1, 1, 1, 'GENESIS', 'Sega Genesis / Mega Drive');
INSERT INTO studios (studio_id, studio_code, studio_name) VALUES (1, 'SONIC_TEAM', 'Sonic Team');
INSERT INTO franchises (franchise_id, studio_id, franchise_name) VALUES (1, 1, 'Sonic the Hedgehog');
INSERT INTO genres (genre_id, genre_name) VALUES (1, 'Platformer');
INSERT INTO licenses (license_id, license_code, license_name) VALUES (1, 'PROPRIETARY', 'Closed Source Commercial Rights');
INSERT INTO statuses (status_id, status_code, status_name) VALUES (1, 'ARCHIVED', 'Fully Preserved and Verified');
INSERT INTO link_types (type_id, type_code, type_name) VALUES (1, 'TROPHIES', 'Trophies / Achievements'), (5, 'DATABASE', 'Archival Database');
INSERT INTO affiliates (affiliate_id, affiliate_code, affiliate_name, base_url) VALUES 
(1, 'MOBYGAMES', 'MobyGames', 'https://www.mobygames.com'),
(2, 'RETROACHIEVEMENTS', 'RetroAchievements', 'https://retroachievements.org');

INSERT INTO games (game_id, franchise_id, origin_id, platform_id, genre_id, license_id, status_id, canonical_title, first_release_year)
VALUES (1, 1, 1, 1, 1, 1, 1, 'Sonic the Hedgehog', 1991);

INSERT INTO links (game_id, type_id, affiliate_id, url) VALUES 
(1, 5, 1, 'https://www.mobygames.com/game/704/sonic-the-hedgehog/'),
(1, 1, 2, 'https://retroachievements.org/game/1');