<?php

declare(strict_types=1);

require_once __DIR__ . '/sql-connect.php';
require_once __DIR__ . '/build-db.php';

$pdo = getDbConnection();
if (!$pdo) {
    die("<h1>Database Connection Failed</h1>");
}

$actionMessage = null;
$actionSuccess = null;

if (isset($_POST['run_migration'])) {
    $actionSuccess = runDatabaseMigration($pdo);
    $actionMessage = $actionSuccess 
        ? "Database successfully migrated and seeded from JSON." 
        : "Migration failed. Check database server or seed file syntax.";
}

// Complete schema dictionary for robust tooltip hover hints across all tables
$columnTooltips = [
    'game_id'          => 'Surrogate primary key for master IP catalog entry',
    'canonical_title'  => 'Standardized English release title of the game',
    'franchise_name'   => 'Parent intellectual property franchise grouping',
    'vendor_code'      => 'Official hardware manufacturer code (e.g. SEGA, SONY)',
    'platform_name'    => 'Official hardware target console market name',
    'links'            => 'Normalized external archival reference links (ROMs, Trophies, Databases)',
    'origin_code'      => 'ISO Alpha-3 country code representing territory of origin',
    'origin_name'      => 'Full localized country or territory name',
    'media_code'       => 'Physical storage medium format (derived from platform configuration)',
    'media_name'       => 'Full technical name of storage media format',
    'first_release_year' => 'Chronological year of initial worldwide commercial release',
    'license_code'     => 'Legal copyright and licensing classification',
    'genre_name'       => 'Core gameplay genre classification category',
    'status_code'      => 'Archival preservation verification status'
];

$games = [];
$checkCol = 0;

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'links'");
    $checkCol = $tableCheck->rowCount();
    
    if ($checkCol > 0) {
        $stmt = $pdo->query("
            SELECT g.*, 
                   f.franchise_name, 
                   o.origin_code, 
                   o.origin_name, 
                   m.media_code,
                   m.media_name,
                   ge.genre_name, 
                   l.license_code,
                   s.status_code,
                   p.platform_name,
                   p.platform_code,
                   v.vendor_name,
                   v.vendor_code
            FROM games g 
            LEFT JOIN franchises f ON g.franchise_id = f.franchise_id
            LEFT JOIN origins o ON g.origin_id = o.origin_id
            LEFT JOIN platforms p ON g.platform_id = p.platform_id
            LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
            LEFT JOIN media_types m ON p.media_id = m.media_id
            LEFT JOIN genres ge ON g.genre_id = ge.genre_id
            LEFT JOIN licenses l ON g.license_id = l.license_id
            LEFT JOIN statuses s ON g.status_id = s.status_id
            ORDER BY g.game_id ASC
        ");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($games as &$game) {
            $linkStmt = $pdo->prepare("
                SELECT l.url, lt.type_name, a.affiliate_name 
                FROM links l
                JOIN link_types lt ON l.type_id = lt.type_id
                JOIN affiliates a ON l.affiliate_id = a.affiliate_id
                WHERE l.game_id = ?
            ");
            $linkStmt->execute([$game['game_id']]);
            $game['links'] = $linkStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($game);
    }
} catch (PDOException $e) {
    $checkCol = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retro Archive - Master IP Catalog</title>
    <link rel="stylesheet" href="style.css">
    <style>
        th[title], span[title], small[title], td[title] {
            cursor: help;
            border-bottom: 1px dotted #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div>
            <h1>Master IP Catalog</h1>
            <p>Museum-grade preservation index with interactive database schema tooltips.</p>
        </div>
        <div class="action-buttons">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="run_migration" 
                        class="btn-build">
                    ⚡ Run DB Build & Migration
                </button>
            </form>
        </div>
    </div>

    <?php if ($actionMessage !== null): ?>
        <div class="<?= $actionSuccess ? 'alert-success' 
                                      : 'alert-error' ?>">
            <strong><?= $actionSuccess ? 'Success:' : 'Error:' ?></strong> 
            <?= htmlspecialchars($actionMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($checkCol === 0 && $actionMessage === null): ?>
        <div class="notice">
            <strong>Notice:</strong> Database tables have not been 
            created or are out of date. Click the 
            <strong>Run DB Build & Migration</strong> button above.
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th title="<?= htmlspecialchars($columnTooltips['game_id']) ?>">ID</th>
                <th title="<?= htmlspecialchars($columnTooltips['canonical_title']) ?>">Canonical Title</th>
                <th title="<?= htmlspecialchars($columnTooltips['franchise_name']) ?>">Franchise</th>
                <th title="Hardware vendor and target console platform linked via platforms & vendors tables">Vendor / Platform</th>
                <th title="<?= htmlspecialchars($columnTooltips['links']) ?>">Archival Links</th>
                <th title="<?= htmlspecialchars($columnTooltips['origin_code']) ?>">Origin</th>
                <th title="Physical storage medium format derived directly from platform configuration">Media <span style="font-size: 10px; color: #2563eb;" title="Derived data from platform configuration">⚡</span></th>
                <th title="<?= htmlspecialchars($columnTooltips['first_release_year']) ?>">Year</th>
                <th title="<?= htmlspecialchars($columnTooltips['license_code']) ?>">License</th>
                <th title="<?= htmlspecialchars($columnTooltips['genre_name']) ?>">Genre</th>
                <th title="<?= htmlspecialchars($columnTooltips['status_code']) ?>">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($games)): ?>
            <tr>
                <td colspan="11" style="text-align: center; 
                                       color: #64748b; padding: 2rem;">
                    No records found. Run the migration to 
                    populate the database.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($games as $row): ?>
                <tr>
                    <td title="game_id: <?= htmlspecialchars((string) $row['game_id']) ?>">
                        <?= htmlspecialchars((string) $row['game_id']) ?>
                    </td>
                    <td><strong><?= htmlspecialchars((string) $row['canonical_title']) ?></strong></td>
                    <td title="franchise_id: <?= htmlspecialchars((string) ($row['franchise_id'] ?? 'N/A')) ?>">
                        <?= htmlspecialchars((string) ($row['franchise_name'] ?? 'N/A')) ?>
                    </td>
                    <td>
                        <span class="badge" style="background: #e2e8f0; color: #1e293b;" title="<?= htmlspecialchars($columnTooltips['vendor_code']) ?>">
                            <?= htmlspecialchars((string) ($row['vendor_code'] ?? 'UNKNOWN')) ?>
                        </span><br>
                        <small title="<?= htmlspecialchars($columnTooltips['platform_name']) ?>"><strong><?= htmlspecialchars((string) ($row['platform_name'] ?? 'N/A')) ?></strong></small>
                    </td>
                    <td>
                        <?php if (!empty($row['links'])): ?>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                            <?php foreach ($row['links'] as $link): ?>
                                <a href="<?= htmlspecialchars((string) $link['url']) ?>" 
                                   target="_blank" style="font-size: 11px; text-decoration: none; color: #2563eb;"
                                   title="External reference link to <?= htmlspecialchars((string) $link['affiliate_name']) ?>">
                                   <strong>[<?= htmlspecialchars((string) $link['type_name']) ?>]</strong> 
                                   <?= htmlspecialchars((string) $link['affiliate_name']) ?>
                                </a>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 11px;">No Links</span>
                        <?php endif; ?>
                    </td>
                    <td title="Origin Territory: <?= htmlspecialchars((string) ($row['origin_name'] ?? 'N/A')) ?>">
                        <?= htmlspecialchars((string) ($row['origin_code'] ?? 'N/A')) ?>
                    </td>
                    <td>
                        <span class="badge" title="Derived Media Format: <?= htmlspecialchars((string) ($row['media_name'] ?? 'Unknown')) ?>">
                            <?= htmlspecialchars((string) ($row['media_code'] ?? 'Unknown')) ?>
                        </span>
                    </td>
                    <td title="first_release_year: <?= htmlspecialchars((string) $row['first_release_year']) ?>">
                        <?= htmlspecialchars((string) $row['first_release_year']) ?>
                    </td>
                    <td><span class="badge" title="license_code: <?= htmlspecialchars((string) ($row['license_code'] ?? 'Pending')) ?>"><?= htmlspecialchars((string) ($row['license_code'] ?? 'Pending')) ?></span></td>
                    <td><span title="genre_name: <?= htmlspecialchars((string) ($row['genre_name'] ?? 'Pending')) ?>"><?= htmlspecialchars((string) ($row['genre_name'] ?? 'Pending')) ?></span></td>
                    <td><span class="badge" title="status_code: <?= htmlspecialchars((string) ($row['status_code'] ?? 'Pending')) ?>"><?= htmlspecialchars((string) ($row['status_code'] ?? 'Pending')) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>