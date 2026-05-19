<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;

$php = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : (PHP_BINARY ?: 'php');
$pdo = DB::pdo();

$adminId = (int)$pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_deleted = 0 LIMIT 1")->fetchColumn();
$citizenId = (int)$pdo->query("SELECT id FROM users WHERE role = 'citizen' AND is_deleted = 0 LIMIT 1")->fetchColumn();

function apiGet(string $php, int $userId, string $path): array
{
    $worker = __DIR__ . '/analytics_worker.php';
    $cmd = [$php, $worker, (string)$userId, $path];
    $pipes = [];
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__ . '/..');
    if (!is_resource($proc)) {
        throw new RuntimeException('proc_open failed');
    }
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    if (trim((string)$out) === '') {
        throw new RuntimeException("Empty output ($code): $err");
    }
    return json_decode(trim($out), true, 512, JSON_THROW_ON_ERROR);
}

$failures = [];

try {
    $data = apiGet($php, $adminId, 'api/analytics/distribution.php');
    if (!($data['ok'] ?? false)) {
        $failures[] = 'admin distribution not ok';
    } else {
        echo "[OK] admin distribution 200\n";
    }

    $activeTotal = (int)$pdo->query('SELECT COUNT(*) FROM reports WHERE is_deleted = 0')->fetchColumn();
    $deletedTotal = (int)$pdo->query('SELECT COUNT(*) FROM reports WHERE is_deleted = 1')->fetchColumn();
    $sumStatus = array_sum(array_column($data['status'] ?? [], 'count'));
    $rejected = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted = 0 AND status = 'rejected'")->fetchColumn();
    if ($sumStatus + $rejected !== $activeTotal) {
        $failures[] = "status chart sum $sumStatus + rejected $rejected != active $activeTotal";
    } else {
        echo "[OK] status chart matches active reports (rejected shown only in KPIs; deleted: $deletedTotal)\n";
    }

    $priSum = array_sum(array_column($data['priority'] ?? [], 'count'));
    if ($priSum !== $activeTotal) {
        $failures[] = "priority sum $priSum != active $activeTotal";
    } else {
        echo "[OK] priority distribution totals\n";
    }

    $citizen = apiGet($php, $citizenId, 'api/analytics/distribution.php');
    if (($citizen['error'] ?? '') !== 'Forbidden') {
        $failures[] = 'citizen should get Forbidden, got: ' . json_encode($citizen);
    } else {
        echo "[OK] non-admin 403 Forbidden\n";
    }
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
    echo "[FAIL] " . $e->getMessage() . "\n";
}

if ($failures) {
    foreach ($failures as $f) {
        echo "[FAIL] $f\n";
    }
    exit(1);
}
echo "Analytics distribution tests passed.\n";
