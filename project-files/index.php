<?php

declare(strict_types=1);

require_once __DIR__ . '/sql-connect.php';

$pdo = getDbConnection();
if (!$pdo) {
    die("<h1>Database Connection Failed</h1>");
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
        die("<h1>Tables Not Found</h1><p>Database tables have " .
            "not been created yet. Please run " .
            "<a href='build-db.php'>build-db.php</a> first.</p>");
    }
    throw $e;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retro Archive - Master IP Catalog</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 
                         "Segoe UI", Roboto, sans-serif; 
            margin: 2rem; background: #f8fafc; color: #1e293b; 
        }
        h1 { margin-bottom: 0.5rem; }
        p { color: #64748b; margin-top: 0; }
        table { 
            width: 100%; border-collapse: collapse; background: #ffffff; 
            margin-top: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            border-radius: 8px; overflow: hidden; 
        }
        th, td { 
            padding: 12px 16px; border-bottom: 1px solid #e2e8f0; 
            text-align: left; font-size: 13px; 
        }
        th { background-color: #0f172a; color: #f8fafc; font-weight: 600; }
        tr:hover { background-color: #f1f5f9; }
        .badge { 
            display: inline-block; padding: 2px 6px; border-radius: 4px; 
            font-size: 10px; font-weight: 700; background: #e2e8f0; 
            color: #475569; text-transform: uppercase; 
        }
        .linked-field { color: #ef4444; font-weight: bold; cursor: help; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .notice { 
            background: #fef3c7; border: 1px solid #f59e0b; 
            padding: 10px 15px; border-radius: 6px; margin-bottom: 1rem; 
            color: #92400e; font-size: 13px; 
        }
    </style>
</head>
<body>
    <h1>Master IP Catalog</h1>
    <p>Museum-grade preservation index with fully normalized lookup tables.</p>

    <?php if ($checkCol === 0): ?>
        <div class="notice">
            <strong>Notice:</strong> Database schema is out of date. 
            Please run <a href="build-db.php">build-db.php</a> to apply 
            the latest normalized table columns.
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
            <?php foreach ($games as $row): ?>
            <tr>
                <td><?= htmlspecialchars((string) $row['game_id']) ?></td>
                <td><strong><?= htmlspecialchars((string) $row['canonical_title']) ?></strong></td>
                <td><?= htmlspecialchars((string) ($row['franchise_name'] ?? 'N/A')) ?></td>
                <td>
                    <?php if (!empty($row['wikidata_qid'])): ?>
                        <a href="https://www.wikidata.org/wiki/<?= htmlspecialchars((string) $row['wikidata_qid']) ?>" 
                           target="_blank"><?= htmlspecialchars((string) $row['wikidata_qid']) ?></a>
                    <?php else: ?>
                        <span style="color: #94a3b8;">Unlinked</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($row['origin_code'] ?? 'N/A')) ?></td>
                <td><?= htmlspecialchars((string) $row['original_media_type']) ?></td>
                <td><?= htmlspecialchars((string) $row['first_release_year']) ?></td>
                <td><span class="badge"><?= htmlspecialchars((string) ($row['license_code'] ?? 'Pending')) ?></span></td>
                <td><?= htmlspecialchars((string) ($row['genre_name'] ?? 'Pending')) ?></td>
                <td><span class="badge"><?= htmlspecialchars((string) ($row['status_code'] ?? 'Pending')) ?></span></td>
                <td><small><?= htmlspecialchars((string) $row['notes']) ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>