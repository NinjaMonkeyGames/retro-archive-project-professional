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
                   s.status_code 
            FROM games g 
            LEFT JOIN franchises f ON g.franchise_id = f.franchise_id
            LEFT JOIN origins o ON g.origin_id = o.origin_id
            LEFT JOIN media_types m ON g.media_id = m.media_id
            LEFT JOIN genres ge ON g.genre_id = ge.genre_id
            LEFT JOIN licenses l ON g.license_id = l.license_id
            LEFT JOIN statuses s ON g.status_id = s.status_id
            ORDER BY g.game_id ASC
        ");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // JOIN with link_types and affiliates lookup tables
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
</head>
<body>
    <div class="header-container">
        <div>
            <h1>Master IP Catalog</h1>
            <p>Museum-grade preservation index with double-normalized 
               links (types & affiliates).</p>
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

    <?php if ($actionMessage !== null) : ?>
        <div class="<?= $actionSuccess ? 'alert-success'
                                      : 'alert-error' ?>">
            <strong><?= $actionSuccess ? 'Success:' : 'Error:' ?></strong> 
            <?= htmlspecialchars($actionMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($checkCol === 0 && $actionMessage === null) : ?>
        <div class="notice">
            <strong>Notice:</strong> Database tables have not been 
            created or are out of date. Click the 
            <strong>Run DB Build & Migration</strong> button above.
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Canonical Title</th>
                <th>Franchise <span class="linked-field" 
                    title="Loaded via Foreign Key (franchise_id)">*</span></th>
                <th>Archival Links <span class="linked-field" 
                    title="Normalized via link_types & affiliates tables">*</span></th>
                <th>Origin <span class="linked-field" 
                    title="Loaded via Foreign Key (origin_id)">*</span></th>
                <th>Media <span class="linked-field" 
                    title="Loaded via Foreign Key (media_id)">*</span></th>
                <th>Year</th>
                <th>License <span class="linked-field" 
                    title="Loaded via Foreign Key (license_id)">*</span></th>
                <th>Genre <span class="linked-field" 
                    title="Loaded via Foreign Key (genre_id)">*</span></th>
                <th>Status <span class="linked-field" 
                    title="Loaded via Foreign Key (status_id)">*</span></th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($games)) : ?>
            <tr>
                <td colspan="11" style="text-align: center; 
                                       color: #64748b; padding: 2rem;">
                    No records found. Run the migration to 
                    populate the database.
                </td>
            </tr>
            <?php else : ?>
                <?php foreach ($games as $row) : ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['game_id']) ?></td>
                    <td><strong><?= htmlspecialchars((string)
                        $row['canonical_title']) ?></strong></td>
                    <td><?= htmlspecialchars((string)
                        ($row['franchise_name'] ?? 'N/A')) ?></td>
                    <td>
                        <?php if (!empty($row['links'])) : ?>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                            <?php foreach ($row['links'] as $link) : ?>
                                <a href="<?= htmlspecialchars((string) $link['url']) ?>" 
                                   target="_blank" style="font-size: 11px; text-decoration: none; color: #2563eb;">
                                   <strong>[<?= htmlspecialchars((string) $link['type_name']) ?>]</strong> 
                                   <?= htmlspecialchars((string) $link['affiliate_name']) ?>
                                </a>
                            <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <span style="color: #94a3b8; font-size: 11px;">No Links</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)
                        ($row['origin_code'] ?? 'N/A')) ?></td>
                    <td><span class="badge"><?= htmlspecialchars((string)
                        ($row['media_code'] ?? 'Unknown')) ?></span></td>
                    <td><?= htmlspecialchars((string)
                        $row['first_release_year']) ?></td>
                    <td><span class="badge"><?=
                        htmlspecialchars((string)
                        ($row['license_code'] ?? 'Pending')) ?></span></td>
                    <td><?= htmlspecialchars((string)
                        ($row['genre_name'] ?? 'Pending')) ?></td>
                    <td><span class="badge"><?=
                        htmlspecialchars((string)
                        ($row['status_code'] ?? 'Pending')) ?></span></td>
                    <td><small><?= htmlspecialchars((string)
                        $row['notes']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>