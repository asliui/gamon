<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\ActivityLog;
use WebGamon\Core\DB;

$php = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : (PHP_BINARY ?: 'php');
$worker = __DIR__ . DIRECTORY_SEPARATOR . 'analytics_worker.php';
$pdo = DB::pdo();

$adminId = (int)$pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_deleted = 0 LIMIT 1")->fetchColumn();
$citizenId = (int)$pdo->query("SELECT id FROM users WHERE role = 'citizen' AND is_deleted = 0 LIMIT 1")->fetchColumn();

function apiGet(string $php, string $worker, int $userId, string $path): array
{
    $cmd = [$php, $worker, (string)$userId, $path];
    $pipes = [];
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__ . '/..');
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    return json_decode(trim((string)$out), true, 512, JSON_THROW_ON_ERROR);
}

$failures = [];
$before = (int)$pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();

ActivityLog::write($adminId, 'report_created', 'report', 99999, ['test' => true]);
$after = (int)$pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
if ($after <= $before) {
    $failures[] = 'ActivityLog::write did not insert row';
} else {
    echo "[OK] ActivityLog::write inserts row\n";
    $pdo->exec('DELETE FROM activity_logs WHERE entity_id = 99999 AND action = \'report_created\'');
}

ActivityLog::write($adminId, 'report_created', 'report', null, ["bad\x80" => "\xFF\xFE"]);
echo "[OK] ActivityLog handles odd details without throwing\n";

try {
    $adminRes = apiGet($php, $worker, $adminId, 'api/admin/activity-log.php?page=1&per_page=20');
    if (!($adminRes['ok'] ?? false)) {
        $failures[] = 'admin activity-log not ok';
    } else {
        echo "[OK] admin activity-log API\n";
    }
} catch (Throwable $e) {
    $failures[] = 'admin API: ' . $e->getMessage();
}

try {
    $citizenRes = apiGet($php, $worker, $citizenId, 'api/admin/activity-log.php');
    if (($citizenRes['error'] ?? '') !== 'Forbidden') {
        $failures[] = 'citizen should get Forbidden';
    } else {
        echo "[OK] citizen forbidden\n";
    }
} catch (Throwable $e) {
    $failures[] = 'citizen test: ' . $e->getMessage();
}

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activity_logs'")->fetch();
if (!$tables) {
    $failures[] = 'activity_logs table missing';
} else {
    echo "[OK] activity_logs table exists\n";
}

echo "\n" . (count($failures) ? implode("\n", $failures) : 'Activity log tests passed.') . "\n";
exit($failures ? 1 : 0);
