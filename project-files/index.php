<?php

declare(strict_types=1);

require_once __DIR__ . '/sql-connect.php';
require_once __DIR__ . '/db-actions.php';

$pdo = getDbConnection();
if (!$pdo) {
    die("<h1>Database Connection Failed</h1>");
}

$actionMessage = null;
$actionSuccess = null;

// Handle form actions
if (isset($_POST['run_migration'])) {
    $actionSuccess = runDatabaseMigration($pdo);
    $actionMessage = $actionSuccess
        ? "Database successfully migrated and seeded from JSON."
        : "Migration failed. Check database server or seed file syntax.";
} elseif (isset($_POST['save_to_file'])) {
    downloadDatabaseFile($pdo);
}

$games = [];
$checkCol = 0;

try {
    $checkCol = $pdo->query(
        "SHOW COLUMNS FROM games LIKE 'status_id'"
    )->rowCount();

    if ($checkCol > 0) {
        $stmt = $pdo->query("
            SELECT g.*, 
                   f.franchise_name, 
                   o.origin_code, 
                   o.origin_name, 
                   ge.genre_name, 
                   l.license_code,
                   s.status_code 
            FROM games g 
            LEFT JOIN franchises f ON g.franchise_id = f.franchise_id
            LEFT JOIN origins o ON g.origin_id = o.origin_id
            LEFT JOIN genres ge ON g.genre_id = ge.genre_id
            LEFT JOIN licenses l ON g.license_id = l.license_id
            LEFT JOIN statuses s ON g.status_id = s.status_id
            ORDER BY g.game_id ASC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT g.*, 
                   f.franchise_name, 
                   o.origin_code, 
                   o.origin_name,
                   NULL as genre_name,
                   NULL as license_code,
                   NULL as status_code
            FROM games g 
            LEFT JOIN franchises f ON g.franchise_id = f.franchise_id
            LEFT JOIN origins o ON g.origin_id = o.origin_id
            ORDER BY g.game_id ASC
        ");
    }
    $games = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        $checkCol = 0; // Tables not created yet
    } else {
        throw $e;
    }
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
            <p>Museum-grade preservation index with fully 
               normalized lookup tables.</p>
        </div>
        <div class="action-buttons">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="run_migration" 
                        class="btn-build">
                    ⚡ Run DB Build & Migration
                </button>
            </form>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="save_to_file" 
                        class="btn-save">
                    💾 Download `game-data.json`
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
                <th>Wikidata QID</th>
                <th>Origin <span class="linked-field" 
                    title="Loaded via Foreign Key (origin_id)">*</span></th>
                <th>Media</th>
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
                        <?php if (!empty($row['wikidata_qid'])) : ?>
                            <a href="https://www.wikidata.org/wiki/<?=
                               htmlspecialchars((string)
                               $row['wikidata_qid']) ?>" 
                               target="_blank"><?=
                                htmlspecialchars((string)
                                $row['wikidata_qid']) ?></a>
                        <?php else : ?>
                            <span style="color: #94a3b8;">Unlinked</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)
                        ($row['origin_code'] ?? 'N/A')) ?></td>
                    <td><?= htmlspecialchars((string)
                        $row['original_media_type']) ?></td>
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