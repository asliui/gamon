<?php

declare(strict_types=1);

// api/reports/assign.php
// Assigns a report to a personnel. Personnel can assign tasks to themselves.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

// FIX: Allow both admin and personnel to use this API
$user = Auth::requireRole('admin', 'personnel');
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');

// If the user is an admin, they must provide the personnel_id they are assigning to.
// If the user is personnel, they automatically assign the task to themselves.
if ($user['role'] === 'admin') {
    $errors['personnel_id'] = Validator::int($data, 'personnel_id');
}

$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];
$personnelId = ($user['role'] === 'personnel') ? (int)$user['id'] : (int)$data['personnel_id'];

// Insert or update the assignment in the database
$stmt = DB::pdo()->prepare('
  INSERT INTO assignments (report_id, personnel_id)
  VALUES (:report_id, :personnel_id)
  ON CONFLICT(report_id) DO UPDATE SET personnel_id = excluded.personnel_id, assigned_at = datetime(\'now\')
');

$stmt->execute([
    ':report_id' => $reportId,
    ':personnel_id' => $personnelId,
]);

// Update the main report status to 'assigned'
DB::pdo()->prepare('UPDATE reports SET status = :status, updated_at = datetime(\'now\') WHERE id = :id')
    ->execute([':status' => 'assigned', ':id' => $reportId]);

Response::json(['ok' => true]);