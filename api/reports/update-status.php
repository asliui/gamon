<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\ActivityLog;
use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\ReportStatus;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$user = Auth::requireRole('admin', 'personnel');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');
$errors['status'] = Validator::oneOf($data, 'status', ['open', 'assigned', 'in_progress', 'resolved', 'rejected']);
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];
$newStatus = (string)$data['status'];

$reportStmt = DB::pdo()->prepare('SELECT id, status, is_deleted FROM reports WHERE id = :id');
$reportStmt->execute([':id' => $reportId]);
$report = $reportStmt->fetch();
if (!$report || (int)($report['is_deleted'] ?? 0) === 1) {
    Response::json(['ok' => false, 'error' => 'Report not found'], 404);
}

$currentStatus = (string)$report['status'];
$isAdmin = $user['role'] === 'admin';

if (!$isAdmin) {
    $check = DB::pdo()->prepare('
      SELECT 1 FROM assignments
      WHERE report_id = :report_id AND personnel_id = :personnel_id
      LIMIT 1
    ');
    $check->execute([
        ':report_id' => $reportId,
        ':personnel_id' => (int)$user['id'],
    ]);
    if (!$check->fetchColumn()) {
        Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
    }
}

ReportStatus::assertCanTransition($currentStatus, $newStatus, $isAdmin);

$resolvedSql = '';
if ($newStatus === 'resolved' && $currentStatus !== 'resolved') {
    $resolvedSql = ", resolved_at = datetime('now')";
} elseif ($newStatus !== 'resolved' && $currentStatus === 'resolved') {
    $resolvedSql = ', resolved_at = NULL';
}

DB::pdo()->prepare("UPDATE reports SET status = :status, updated_at = datetime('now'){$resolvedSql} WHERE id = :id")
    ->execute([':status' => $newStatus, ':id' => $reportId]);

ActivityLog::write((int)$user['id'], 'report_status_changed', 'report', $reportId, [
    'old_status' => $currentStatus,
    'new_status' => $newStatus,
]);

Response::json(['ok' => true]);
