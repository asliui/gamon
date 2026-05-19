<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\AssignmentHistory;
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
if ($user['role'] === 'admin') {
    $errors['personnel_id'] = Validator::int($data, 'personnel_id');
}
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];
$personnelId = ($user['role'] === 'personnel') ? (int)$user['id'] : (int)$data['personnel_id'];

$reportStmt = DB::pdo()->prepare('SELECT id, status, is_deleted FROM reports WHERE id = :id');
$reportStmt->execute([':id' => $reportId]);
$report = $reportStmt->fetch();
if (!$report || (int)($report['is_deleted'] ?? 0) === 1) {
    Response::json(['ok' => false, 'error' => 'Report not found'], 404);
}

$reportStatus = (string)($report['status'] ?? '');

if ($user['role'] === 'admin') {
    if (in_array($reportStatus, ['resolved', 'rejected'], true)) {
        Response::json(['ok' => false, 'error' => 'Cannot assign personnel to a closed report.'], 409);
    }

    $personnelStmt = DB::pdo()->prepare('
        SELECT id FROM users
        WHERE id = :id AND role = :role AND is_deleted = 0
    ');
    $personnelStmt->execute([':id' => $personnelId, ':role' => 'personnel']);
    if (!$personnelStmt->fetch()) {
        Response::json(['ok' => false, 'error' => 'Invalid or inactive personnel.'], 422);
    }
} else {
    if ($reportStatus !== 'open') {
        Response::json(['ok' => false, 'error' => 'Report is not open for assignment.'], 409);
    }

    $existingStmt = DB::pdo()->prepare('SELECT personnel_id FROM assignments WHERE report_id = :report_id');
    $existingStmt->execute([':report_id' => $reportId]);
    $existing = $existingStmt->fetch();
    if ($existing && (int)$existing['personnel_id'] !== (int)$user['id']) {
        Response::json(['ok' => false, 'error' => 'Report is already assigned to another personnel.'], 409);
    }
}

$oldPersonnelId = null;
$existingStmt = DB::pdo()->prepare('SELECT personnel_id FROM assignments WHERE report_id = :report_id');
$existingStmt->execute([':report_id' => $reportId]);
$existingRow = $existingStmt->fetch();
if ($existingRow) {
    $oldPersonnelId = (int)$existingRow['personnel_id'];
    if ($oldPersonnelId === $personnelId) {
        Response::json(['ok' => true, 'report_id' => $reportId, 'personnel_id' => $personnelId, 'unchanged' => true]);
    }
}

$pdo = DB::pdo();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('
      INSERT INTO assignments (report_id, personnel_id)
      VALUES (:report_id, :personnel_id)
      ON CONFLICT(report_id) DO UPDATE SET
        personnel_id = excluded.personnel_id,
        assigned_at = datetime(\'now\'),
        progress_status = CASE
          WHEN assignments.personnel_id != excluded.personnel_id THEN \'not_started\'
          ELSE assignments.progress_status
        END,
        progress_note = CASE
          WHEN assignments.personnel_id != excluded.personnel_id THEN NULL
          ELSE assignments.progress_note
        END,
        progress_updated_at = CASE
          WHEN assignments.personnel_id != excluded.personnel_id THEN NULL
          ELSE assignments.progress_updated_at
        END
    ');
    $stmt->execute([
        ':report_id' => $reportId,
        ':personnel_id' => $personnelId,
    ]);

    AssignmentHistory::record($reportId, $oldPersonnelId, $personnelId, (int)$user['id'], $pdo);

    $newStatus = ReportStatus::statusAfterAssignment($reportStatus);
    $pdo->prepare('UPDATE reports SET status = :status, updated_at = datetime(\'now\') WHERE id = :id')
        ->execute([':status' => $newStatus, ':id' => $reportId]);

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    Response::json(['ok' => false, 'error' => 'Assignment failed'], 500);
}

Response::json(['ok' => true, 'report_id' => $reportId, 'personnel_id' => $personnelId]);
