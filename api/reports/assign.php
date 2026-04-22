<?php

declare(strict_types=1);

// api/reports/assign.php
// Placeholder: assign a report to a personnel (admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

Auth::requireRole('admin');
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');
$errors['personnel_id'] = Validator::int($data, 'personnel_id');
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

// Upsert assignment.
$stmt = DB::pdo()->prepare('
  INSERT INTO assignments (report_id, personnel_id)
  VALUES (:report_id, :personnel_id)
  ON CONFLICT(report_id) DO UPDATE SET personnel_id = excluded.personnel_id, assigned_at = datetime(\'now\')
');
$stmt->execute([
    ':report_id' => (int)$data['report_id'],
    ':personnel_id' => (int)$data['personnel_id'],
]);

DB::pdo()->prepare('UPDATE reports SET status = :status, updated_at = datetime(\'now\') WHERE id = :id')
    ->execute([':status' => 'assigned', ':id' => (int)$data['report_id']]);

Response::json(['ok' => true]);

