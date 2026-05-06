<?php

declare(strict_types=1);

// api/reports/update-status.php
// Placeholder: update report status (personnel/admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$user = Auth::requireRole('admin', 'personnel');
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

// Personnel can only update statuses for reports assigned to themselves.
if ($user['role'] === 'personnel') {
    $check = DB::pdo()->prepare('
      SELECT 1
      FROM assignments
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

$stmt = DB::pdo()->prepare('UPDATE reports SET status = :status, updated_at = datetime(\'now\') WHERE id = :id');
$stmt->execute([
    ':status' => $newStatus,
    ':id' => $reportId,
]);

Response::json(['ok' => true]);

