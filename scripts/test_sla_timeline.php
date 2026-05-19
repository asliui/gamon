<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\SLA;

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
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    $raw = trim((string)$out);
    if ($raw === '' || $raw[0] !== '{') {
        throw new \RuntimeException('API worker failed: ' . trim($raw . "\n" . $err));
    }
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

$failures = [];

$lowDue = SLA::calculateDueAt('low', '2026-01-01 00:00:00');
$lowDt = new DateTimeImmutable($lowDue, new DateTimeZone('UTC'));
$base = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
$diffDays = (int)$base->diff($lowDt)->days;
if ($diffDays !== 7) {
    $failures[] = "low SLA expected ~7 days, got $diffDays";
} else {
    echo "[OK] low priority +7 days\n";
}

$critDue = SLA::calculateDueAt('critical', '2026-01-01 00:00:00');
$critDt = new DateTimeImmutable($critDue, new DateTimeZone('UTC'));
$diffHours = (int)(($critDt->getTimestamp() - $base->getTimestamp()) / 3600);
if ($diffHours !== 6) {
    $failures[] = "critical SLA expected 6h, got $diffHours";
} else {
    echo "[OK] critical +6 hours\n";
}

$overdueRow = [
    'status' => 'open',
    'due_at' => gmdate('Y-m-d H:i:s', time() - 3600),
    'resolved_at' => null,
];
if (!SLA::isOverdue($overdueRow)) {
    $failures[] = 'expected overdue';
} else {
    echo "[OK] isOverdue\n";
}

$lateRow = [
    'status' => 'resolved',
    'due_at' => '2026-01-01 00:00:00',
    'resolved_at' => '2026-01-02 00:00:00',
];
if (!SLA::isResolvedLate($lateRow)) {
    $failures[] = 'expected resolved late';
} else {
    echo "[OK] isResolvedLate\n";
}

$report = $pdo->prepare('SELECT id FROM reports WHERE is_deleted = 0 AND citizen_id != :cid LIMIT 1');
$report->execute([':cid' => $citizenId]);
$reportRow = $report->fetch();
DB::disconnect();

if ($reportRow) {
    $rid = (int)$reportRow['id'];
    $tl = apiGet($php, $worker, $adminId, 'api/reports/timeline.php?report_id=' . $rid);
    if (!($tl['ok'] ?? false)) {
        $failures[] = 'admin timeline failed';
    } else {
        echo "[OK] admin timeline API\n";
    }

    $citizenTl = apiGet($php, $worker, $citizenId, 'api/reports/timeline.php?report_id=' . $rid);
    if (($citizenTl['ok'] ?? true) !== false) {
        $failures[] = 'citizen should not access another user timeline';
    } else {
        echo "[OK] citizen timeline restricted for other users\n";
    }
}

$summary = apiGet($php, $worker, $adminId, 'api/analytics/summary.php');
if (!isset($summary['overdue_reports'], $summary['sla_compliance_pct'])) {
    $failures[] = 'summary missing SLA KPIs';
} else {
    echo "[OK] analytics SLA KPIs\n";
}

echo "\n" . (count($failures) ? implode("\n", $failures) : 'SLA + timeline tests passed.') . "\n";
exit($failures ? 1 : 0);
