<?php

declare(strict_types=1);

// api/analytics/summary.php
// Minimal summary counts for admin dashboard.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$totalReports = (int)DB::pdo()->query('SELECT COUNT(*) AS c FROM reports')->fetch()['c'];
$pendingReports = (int)DB::pdo()->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'open'")->fetch()['c'];
$cleanedReports = (int)DB::pdo()->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'resolved'")->fetch()['c'];
$totalUsers = (int)DB::pdo()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];

$stmt = DB::pdo()->query("
  SELECT status, COUNT(*) AS count
  FROM reports
  GROUP BY status
");
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

Response::json([
    'ok' => true,
    'total_reports' => $totalReports,
    'pending_reports' => $pendingReports,
    'cleaned_reports' => $cleanedReports,
    'total_users' => $totalUsers,
    'status_breakdown' => $breakdown,
]);

