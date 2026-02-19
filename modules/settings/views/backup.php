<?php
/**
 * Settings — Database Backup
 */
$pageTitle = 'Database Backup';
require_permission('settings_manage');

$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$message = '';

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup') {
    verify_csrf();

    $filename = 'urjiberi_backup_' . date('Y-m-d_His') . '.sql';
    $filepath = $backupDir . '/' . $filename;

    $dbHost = DB_HOST;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;
    $dbPort = defined('DB_PORT') ? DB_PORT : 3306;

    // Try mysqldump first
    $dumpCmd = sprintf(
        'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
        escapeshellarg($dbHost),
        $dbPort,
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($filepath)
    );

    $output = [];
    $return = 0;
    exec($dumpCmd, $output, $return);

    if ($return === 0 && file_exists($filepath) && filesize($filepath) > 100) {
        audit_log('create', 'backup', 0, "Database backup: $filename");
        set_flash('success', "Backup created: $filename (" . format_file_size(filesize($filepath)) . ")");
    } else {
        // Fallback: PHP-based backup
        try {
            $pdo = db_connect();
            $fp  = fopen($filepath, 'w');
            fwrite($fp, "-- Urjiberi School ERP Database Backup\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Database: $dbName\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Table structure
                $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($fp, $create['Create Table'] . ";\n\n");

                // Table data
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $columns = array_keys($rows[0]);
                    $colList = '`' . implode('`, `', $columns) . '`';

                    foreach (array_chunk($rows, 100) as $chunk) {
                        $values = [];
                        foreach ($chunk as $row) {
                            $vals = [];
                            foreach ($row as $val) {
                                $vals[] = $val === null ? 'NULL' : $pdo->quote($val);
                            }
                            $values[] = '(' . implode(', ', $vals) . ')';
                        }
                        fwrite($fp, "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $values) . ";\n\n");
                    }
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fp);

            audit_log('create', 'backup', 0, "Database backup (PHP): $filename");
            set_flash('success', "Backup created: $filename (" . format_file_size(filesize($filepath)) . ")");
        } catch (Throwable $e) {
            if (file_exists($filepath)) unlink($filepath);
            error_log("Backup error: " . $e->getMessage());
            set_flash('error', 'Backup failed. Check server logs.');
        }
    }

    redirect('settings', 'backup');
}

// Handle download
if (($_GET['download'] ?? '') !== '') {
    $file = basename($_GET['download']);
    $path = $backupDir . '/' . $file;
    if (file_exists($path) && str_ends_with($file, '.sql')) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    set_flash('error', 'Backup file not found.');
    redirect('settings', 'backup');
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $file = basename($_POST['file'] ?? '');
    $path = $backupDir . '/' . $file;
    if (file_exists($path) && str_ends_with($file, '.sql')) {
        unlink($path);
        audit_log('delete', 'backup', 0, "Deleted backup: $file");
        set_flash('success', 'Backup deleted.');
    } else {
        set_flash('error', 'File not found.');
    }
    redirect('settings', 'backup');
}

// List existing backups
$backups = [];
foreach (glob($backupDir . '/*.sql') as $f) {
    $backups[] = [
        'name' => basename($f),
        'size' => filesize($f),
        'date' => filemtime($f),
    ];
}
usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);

function format_file_size(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

ob_start();
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Database Backup</h1>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="backup">
            <button type="submit"
                    class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900"
                    onclick="this.disabled=true; this.innerText='Creating…'; this.form.submit();">
                Create Backup
            </button>
        </form>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
        <strong>Note:</strong> Backups are stored on the server in <code>/storage/backups/</code>.
        For production use, also keep off-site copies. The backup uses <code>mysqldump</code> when available,
        otherwise falls back to a PHP-based export.
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Filename</th>
                    <th class="px-4 py-3 text-left font-medium">Size</th>
                    <th class="px-4 py-3 text-left font-medium">Created</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($backups)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No backups found. Create one to get started.</td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $b): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= e($b['name']) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= format_file_size($b['size']) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= date('M j, Y g:i A', $b['date']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url('settings', 'backup') ?>&download=<?= urlencode($b['name']) ?>"
                                   class="text-primary-700 hover:underline text-sm mr-3">Download</a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this backup?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?= e($b['name']) ?>">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
