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
    $result        = runDatabaseMigration($pdo);
    $actionSuccess = $result['success'];
    $actionMessage = $result['message'];
}

/**
 * Loads column comments keyed as "table.column" (lowercase).
 * Tries INFORMATION_SCHEMA first, then falls back to SHOW FULL COLUMNS.
 *
 * @return array{comments: array<string,string>, source: string, error: string}
 */
function loadColumnComments(PDO $pdo): array
{
    $comments = [];
    $error    = '';
    $source   = 'information_schema';

    try {
        $stmt = $pdo->query("
            SELECT TABLE_NAME   AS table_name,
                   COLUMN_NAME  AS column_name,
                   COLUMN_COMMENT AS column_comment
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (($row['column_comment'] ?? '') !== '') {
                $comments[strtolower($row['table_name'] . '.' . $row['column_name'])] = $row['column_comment'];
            }
        }
    } catch (PDOException $e) {
        $error = 'information_schema: ' . $e->getMessage();
    }

    // Fallback: ask each table directly
    if (empty($comments)) {
        $source = 'show_full_columns';
        try {
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $cols = $pdo->query('SHOW FULL COLUMNS FROM `' . str_replace('`', '', (string) $table) . '`');
                while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
                    $col = array_change_key_case($col, CASE_LOWER);
                    if (($col['comment'] ?? '') !== '') {
                        $comments[strtolower($table . '.' . $col['field'])] = $col['comment'];
                    }
                }
            }
        } catch (PDOException $e) {
            $error .= ' | show_full_columns: ' . $e->getMessage();
        }
    }

    return ['comments' => $comments, 'source' => $source, 'error' => $error];
}

/**
 * Builds a safe tooltip string: "<schema comment>[: <value>]".
 * Falls back to $fallback when the schema has no comment.
 */
function tip(array $comments, string $table, string $column, $value = null, string $fallback = ''): string
{
    $text = $comments[strtolower($table . '.' . $column)] ?? $fallback;
    if ($value !== null && $value !== '') {
        $text = $text === '' ? (string) $value : $text . ': ' . $value;
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function e($value, string $default = ''): string
{
    return htmlspecialchars((string) ($value ?? $default), ENT_QUOTES, 'UTF-8');
}

$commentInfo = loadColumnComments($pdo);
$c = $commentInfo['comments'];

$games    = [];
$hasTable = false;

try {
    $hasTable = $pdo->query("SHOW TABLES LIKE 'links'")->rowCount() > 0;

    if ($hasTable) {
        $games = $pdo->query("
            SELECT g.*,
                   f.franchise_name,
                   o.origin_code,
                   o.origin_name,
                   m.media_code,
                   m.media_name,
                   ge.genre_name,
                   l.license_code,
                   l.license_name,
                   s.status_code,
                   s.status_name,
                   p.platform_name,
                   p.platform_code,
                   v.vendor_name,
                   v.vendor_code
            FROM games g
            LEFT JOIN franchises f  ON g.franchise_id = f.franchise_id
            LEFT JOIN origins o     ON g.origin_id    = o.origin_id
            LEFT JOIN platforms p   ON g.platform_id  = p.platform_id
            LEFT JOIN vendors v     ON p.vendor_id    = v.vendor_id
            LEFT JOIN media_types m ON p.media_id     = m.media_id
            LEFT JOIN genres ge     ON g.genre_id     = ge.genre_id
            LEFT JOIN licenses l    ON g.license_id   = l.license_id
            LEFT JOIN statuses s    ON g.status_id    = s.status_id
            ORDER BY g.game_id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $linkStmt = $pdo->prepare("
            SELECT l.url, lt.type_name, a.affiliate_name
            FROM links l
            JOIN link_types lt ON l.type_id = lt.type_id
            JOIN affiliates a  ON l.affiliate_id = a.affiliate_id
            WHERE l.game_id = ?
        ");
        foreach ($games as &$game) {
            $linkStmt->execute([$game['game_id']]);
            $game['links'] = $linkStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($game);
    }
} catch (PDOException $e) {
    $hasTable = false;
    $actionSuccess = false;
    $actionMessage = 'Query error: ' . $e->getMessage();
}

$debug = isset($_GET['debug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retro Archive - Master IP Catalog</title>
    <link rel="stylesheet" href="style.css">
    <style>
        th span, td span, td small {
            cursor: help;
            border-bottom: 1px dotted #2563eb;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div>
            <h1>Master IP Catalog</h1>
            <p>Museum-grade archive with tooltips read dynamically from MySQL schema comments.</p>
        </div>
        <div class="action-buttons">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="run_migration" class="btn-build">
                     Run DB Build &amp; Migration
                </button>
            </form>
        </div>
    </div>

    <?php if ($actionMessage !== null): ?>
        <div class="<?= $actionSuccess ? 'alert-success' : 'alert-error' ?>">
            <strong><?= $actionSuccess ? 'Success:' : 'Error:' ?></strong>
            <?= e($actionMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasTable && $actionMessage === null): ?>
        <div class="notice">
            <strong>Notice:</strong> Database tables have not been created.
            Click the <strong>Run DB Build &amp; Migration</strong> button above.
        </div>
    <?php endif; ?>

    <?php if ($debug): ?>
        <div class="notice">
            <strong>Debug:</strong>
            comments loaded = <?= count($c) ?>,
            source = <?= e($commentInfo['source']) ?>,
            error = <?= e($commentInfo['error'], 'none') ?><br>
            sample: games.game_id = "<?= e($c['games.game_id'] ?? '(missing)') ?>",
            media_types.media_name = "<?= e($c['media_types.media_name'] ?? '(missing)') ?>"
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th><span title="<?= tip($c, 'games', 'game_id', null, 'Game ID') ?>">ID</span></th>
                <th><span title="<?= tip($c, 'games', 'canonical_title', null, 'Canonical Title') ?>">Canonical Title</span></th>
                <th><span title="<?= tip($c, 'franchises', 'franchise_name', null, 'Franchise Name') ?>">Franchise</span></th>
                <th><span title="<?= tip($c, 'platforms', 'platform_name', null, 'Platform Name') ?>">Vendor / Platform</span></th>
                <th><span title="Normalized external archival reference links">Archival Links</span></th>
                <th><span title="<?= tip($c, 'origins', 'origin_code', null, 'Country Origin') ?>">Origin</span></th>
                <th><span title="<?= tip($c, 'media_types', 'media_code', null, 'Storage Media Format') ?>">Media </span></th>
                <th><span title="<?= tip($c, 'games', 'first_release_year', null, 'Release Year') ?>">Year</span></th>
                <th><span title="<?= tip($c, 'licenses', 'license_code', null, 'Copyright License') ?>">License</span></th>
                <th><span title="<?= tip($c, 'genres', 'genre_name', null, 'Game Genre') ?>">Genre</span></th>
                <th><span title="<?= tip($c, 'statuses', 'status_code', null, 'Preservation Status') ?>">Status</span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($games)): ?>
            <tr>
                <td colspan="11" style="text-align: center; color: #64748b; padding: 2rem;">
                    No records found. Run the migration to populate the database.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($games as $row): ?>
                <tr>
                    <td><span title="<?= tip($c, 'games', 'game_id', $row['game_id'], 'Game ID') ?>"><?= e($row['game_id']) ?></span></td>
                    <td><span title="<?= tip($c, 'games', 'canonical_title', $row['canonical_title'], 'Canonical Title') ?>"><strong><?= e($row['canonical_title']) ?></strong></span></td>
                    <td><span title="<?= tip($c, 'franchises', 'franchise_name', $row['franchise_name'], 'Franchise Name') ?>"><?= e($row['franchise_name'], 'N/A') ?></span></td>
                    <td>
                        <span class="badge" style="background: #e2e8f0; color: #1e293b;" title="<?= tip($c, 'vendors', 'vendor_name', $row['vendor_name'], 'Vendor') ?>">
                            <?= e($row['vendor_code'], 'UNKNOWN') ?>
                        </span><br>
                        <small><span title="<?= tip($c, 'platforms', 'platform_name', $row['platform_name'], 'Platform Name') ?>"><strong><?= e($row['platform_name'], 'N/A') ?></strong></span></small>
                    </td>
                    <td>
                        <?php if (!empty($row['links'])): ?>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                            <?php foreach ($row['links'] as $link): ?>
                                <a href="<?= e($link['url']) ?>"
                                   target="_blank" rel="noopener"
                                   style="font-size: 11px; text-decoration: none; color: #2563eb;"
                                   title="<?= tip($c, 'links', 'url', $link['url'], 'External reference link') ?>">
                                   <strong>[<?= e($link['type_name']) ?>]</strong>
                                   <?= e($link['affiliate_name']) ?>
                                </a>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 11px;">No Links</span>
                        <?php endif; ?>
                    </td>
                    <td><span title="<?= tip($c, 'origins', 'origin_name', $row['origin_name'], 'Country Origin') ?>"><?= e($row['origin_code'], 'N/A') ?></span></td>
                    <td>
                        <span class="badge" title="<?= tip($c, 'media_types', 'media_name', $row['media_name'], 'Storage Media Format') ?>">
                            <?= e($row['media_code'], 'Unknown') ?>
                        </span>
                    </td>
                    <td><span title="<?= tip($c, 'games', 'first_release_year', $row['first_release_year'], 'Release Year') ?>"><?= e($row['first_release_year']) ?></span></td>
                    <td><span class="badge" title="<?= tip($c, 'licenses', 'license_name', $row['license_name'], 'Copyright License') ?>"><?= e($row['license_code'], 'Pending') ?></span></td>
                    <td><span title="<?= tip($c, 'genres', 'genre_name', $row['genre_name'], 'Game Genre') ?>"><?= e($row['genre_name'], 'Pending') ?></span></td>
                    <td><span class="badge" title="<?= tip($c, 'statuses', 'status_name', $row['status_name'], 'Preservation Status') ?>"><?= e($row['status_code'], 'Pending') ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>