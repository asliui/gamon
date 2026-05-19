<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$pdo = DB::pdo();
$active = 'is_deleted = 0';

$totalReports = (int)$pdo->query("SELECT COUNT(*) AS c FROM reports WHERE {$active}")->fetch()['c'];
$pendingReports = (int)$pdo->query("SELECT COUNT(*) AS c FROM reports WHERE {$active} AND status = 'open'")->fetch()['c'];
$cleanedReports = (int)$pdo->query("SELECT COUNT(*) AS c FROM reports WHERE {$active} AND status = 'resolved'")->fetch()['c'];
$totalUsers = (int)$pdo->query('SELECT COUNT(*) AS c FROM users WHERE is_deleted = 0')->fetch()['c'];

$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM reports WHERE {$active} GROUP BY status");
$breakdown = [
    'open' => 0,
    'assigned' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'rejected' => 0,
];
foreach ($stmt->fetchAll() as $row) {
    $status = (string)($row['status'] ?? '');
    if ($status !== '' && array_key_exists($status, $breakdown)) {
        $breakdown[$status] = (int)$row['count'];
    }
}

$overdueReports = (int)$pdo->query("
    SELECT COUNT(*) FROM reports
    WHERE is_deleted = 0
      AND status NOT IN ('resolved', 'rejected')
      AND due_at IS NOT NULL
      AND due_at < datetime('now')
")->fetchColumn();

$resolvedLateReports = (int)$pdo->query("
    SELECT COUNT(*) FROM reports
    WHERE is_deleted = 0
      AND status = 'resolved'
      AND due_at IS NOT NULL
      AND resolved_at IS NOT NULL
      AND resolved_at > due_at
")->fetchColumn();

$resolvedWithSla = (int)$pdo->query("
    SELECT COUNT(*) FROM reports
    WHERE is_deleted = 0
      AND status = 'resolved'
      AND due_at IS NOT NULL
      AND resolved_at IS NOT NULL
")->fetchColumn();

$slaCompliancePct = $resolvedWithSla > 0
    ? round((($resolvedWithSla - $resolvedLateReports) / $resolvedWithSla) * 100, 1)
    : 100.0;

Response::json([
    'ok' => true,
    'total_reports' => $totalReports,
    'pending_reports' => $pendingReports,
    'cleaned_reports' => $cleanedReports,
    'total_users' => $totalUsers,
    'status_breakdown' => $breakdown,
    'overdue_reports' => $overdueReports,
    'resolved_late_reports' => $resolvedLateReports,
    'sla_compliance_pct' => $slaCompliancePct,
]);
