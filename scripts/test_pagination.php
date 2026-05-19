<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\ReportPriority;

$pdo = DB::pdo();
$php = 'C:\\xampp\\php\\php.exe';
if (!is_file($php)) {
    $php = PHP_BINARY ?: 'php';
}
$worker = __DIR__ . DIRECTORY_SEPARATOR . 'pagination_worker.php';
$adminId = (int)$pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_deleted = 0 LIMIT 1")->fetchColumn();
$citizenId = (int)$pdo->query("SELECT id FROM users WHERE role = 'citizen' AND is_deleted = 0 LIMIT 1")->fetchColumn();

$failures = [];

function callList(string $php, string $worker, int $userId, array $query): array
{
    $cmd = [$php, $worker, (string)$userId, json_encode($query, JSON_THROW_ON_ERROR)];
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
    if ($code !== 0 && trim($out) === '') {
        throw new RuntimeException("worker exit $code: $err");
    }
    if (trim($out) === '') {
        throw new RuntimeException('Empty API output: ' . $err);
    }
    return json_decode(trim($out), true, 512, JSON_THROW_ON_ERROR);
}

function ok(string $msg): void
{
    echo "[OK] $msg\n";
}

function fail(array &$failures, string $msg): void
{
    $failures[] = $msg;
    echo "[FAIL] $msg\n";
}

function assertEq(array &$failures, mixed $a, mixed $b, string $label): void
{
    if ($a !== $b) {
        fail($failures, "$label: expected " . json_encode($b) . ", got " . json_encode($a));
    } else {
        ok($label);
    }
}

if (!ReportPriority::isValid('bogus')) {
    ok('invalid priority rejected');
} else {
    fail($failures, 'bogus priority should be invalid');
}

$invalidJson = callList($php, $worker, $adminId, ['priority' => 'bogus']);
if (($invalidJson['ok'] ?? true) === false && ($invalidJson['error'] ?? '') === 'Invalid priority') {
    ok('invalid priority returns error JSON');
} else {
    fail($failures, 'invalid priority expected Invalid priority error');
}

$r1 = callList($php, $worker, $adminId, ['page' => 1, 'per_page' => 10]);
assertEq($failures, $r1['ok'] ?? false, true, 'page 1 ok');
assertEq($failures, $r1['per_page'] ?? 0, 10, 'per_page=10');
assertEq($failures, count($r1['items'] ?? []), min(10, $r1['total'] ?? 0), 'items count on page 1');

$rClamp = callList($php, $worker, $adminId, ['per_page' => 999]);
assertEq($failures, $rClamp['per_page'] ?? 0, 50, 'per_page clamped to 50');

$rMin = callList($php, $worker, $adminId, ['per_page' => -5]);
assertEq($failures, $rMin['per_page'] ?? 0, 5, 'negative per_page clamped to 5');

$rLegacy = callList($php, $worker, $adminId, ['limit' => 100]);
assertEq($failures, $rLegacy['per_page'] ?? 0, 50, 'legacy limit maps to per_page 50');

$expectedPages = ($r1['total'] ?? 0) > 0 ? (int)ceil($r1['total'] / 10) : 0;
assertEq($failures, $r1['total_pages'] ?? -1, $expectedPages, 'total_pages math');

if (($r1['total'] ?? 0) > 10) {
    $r2 = callList($php, $worker, $adminId, ['page' => 2, 'per_page' => 10]);
    $ids1 = array_column($r1['items'], 'id');
    $ids2 = array_column($r2['items'], 'id');
    if ($ids1 === $ids2) {
        fail($failures, 'page 2 should differ from page 1');
    } else {
        ok('page 2 differs from page 1');
    }
} else {
    echo "[SKIP] page 2 diff (total <= 10)\n";
}

$rBig = callList($php, $worker, $adminId, ['page' => 9999, 'per_page' => 10]);
assertEq($failures, count($rBig['items'] ?? []), 0, 'oversized page returns empty items');

$rFilter = callList($php, $worker, $adminId, ['priority' => 'medium', 'page' => 1, 'per_page' => 10]);
$leak = false;
foreach ($rFilter['items'] ?? [] as $row) {
    if (($row['priority'] ?? '') !== 'medium') {
        $leak = true;
        break;
    }
}
if ($leak) {
    fail($failures, 'priority filter leaked');
} else {
    ok('priority filter + pagination');
}

$cRes = callList($php, $worker, $citizenId, ['per_page' => 50]);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE citizen_id = :id AND is_deleted = 0');
$stmt->execute([':id' => $citizenId]);
assertEq($failures, $cRes['total'] ?? -1, (int)$stmt->fetchColumn(), 'citizen total scope');

echo "\n" . (count($failures) ? count($failures) . ' failure(s)' : 'All pagination tests passed') . ".\n";
exit($failures ? 1 : 0);
