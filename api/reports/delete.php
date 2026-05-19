<?php

declare(strict_types=1);

// api/reports/delete.php — Admin soft-deletes a report.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

Auth::requireRole('admin');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];

$stmt = DB::pdo()->prepare('
    UPDATE reports
    SET is_deleted = 1, deleted_at = datetime(\'now\'), updated_at = datetime(\'now\')
    WHERE id = :id AND is_deleted = 0
');
$stmt->execute([':id' => $reportId]);

if ($stmt->rowCount() === 0) {
    Response::json(['ok' => false, 'error' => 'Report not found or already deleted'], 404);
}

Response::json(['ok' => true]);
