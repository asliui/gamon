<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;

$php = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : (PHP_BINARY ?: 'php');
$worker = __DIR__ . DIRECTORY_SEPARATOR . 'analytics_worker.php';
$pdo = DB::pdo();

$adminId = (int)$pdo->query("SELECT id FROM users WHERE email = 'asliuzar4@gmail.com' AND is_deleted = 0 LIMIT 1")->fetchColumn();
$citizenId = (int)$pdo->query("SELECT id FROM users WHERE email = 'citizen1@demo.local' AND is_deleted = 0 LIMIT 1")->fetchColumn();

function apiGet(string $php, string $worker, int $userId, string $path): array
{
    DB::disconnect();
    $cmd = [$php, $worker, (string)$userId, $path];
    $pipes = [];
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__ . '/..');
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    $raw = trim((string)$out);
    if ($raw === '' || $raw[0] !== '{') {
        throw new \RuntimeException('API worker failed: ' . $raw);
    }
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

DB::disconnect();

$failures = [];

foreach (['overdue', 'due_soon', 'on_time', 'resolved_late'] as $filter) {
    $res = apiGet($php, $worker, $adminId, 'api/reports/list.php?sla_status=' . $filter . '&per_page=50');
    if (!($res['ok'] ?? false)) {
        $failures[] = "admin sla_status=$filter failed";
        continue;
    }
    echo "[OK] admin sla_status=$filter total=" . ($res['total'] ?? 0) . "\n";
}

$citizenRes = apiGet($php, $worker, $citizenId, 'api/reports/list.php?sla_status=overdue');
if (($citizenRes['ok'] ?? true) !== false) {
    $failures[] = 'citizen should not use sla_status filter';
} else {
    echo "[OK] citizen sla_status forbidden\n";
}

$invalidRes = apiGet($php, $worker, $adminId, 'api/reports/list.php?sla_status=invalid');
if (($invalidRes['ok'] ?? true) !== false) {
    $failures[] = 'invalid sla_status should return ok=false';
} else {
    echo "[OK] invalid sla_status rejected\n";
}

$combo = apiGet($php, $worker, $adminId, 'api/reports/list.php?sla_status=overdue&priority=critical&page=1&per_page=10');
if (!($combo['ok'] ?? false)) {
    $failures[] = 'combined filters failed';
} else {
    echo "[OK] sla_status + priority + pagination\n";
}

echo "\n" . (count($failures) ? implode("\n", $failures) : 'SLA filter tests passed.') . "\n";
exit($failures ? 1 : 0);
