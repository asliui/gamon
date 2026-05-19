<?php

declare(strict_types=1);

// api/reports/update-assignment-progress.php — Personnel updates own assignment progress.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\ActivityLog;
use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$user = Auth::requireRole('personnel');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');
$errors['progress_status'] = Validator::oneOf($data, 'progress_status', ['not_started', 'in_progress', 'completed']);
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];
$progressStatus = (string)$data['progress_status'];
$progressNote = isset($data['progress_note']) ? trim((string)$data['progress_note']) : '';
if (mb_strlen($progressNote) > 2000) {
    Response::json(['ok' => false, 'error' => 'Progress note is too long (max 2000 characters).'], 422);
}

$reportStmt = DB::pdo()->prepare('SELECT id FROM reports WHERE id = :id AND is_deleted = 0');
$reportStmt->execute([':id' => $reportId]);
if (!$reportStmt->fetch()) {
    Response::json(['ok' => false, 'error' => 'Report not found'], 404);
}

$stmt = DB::pdo()->prepare('
    SELECT
        asm.id,
        asm.report_id,
        asm.personnel_id,
        asm.assigned_at,
        asm.progress_status,
        asm.progress_note,
        asm.progress_updated_at
    FROM assignments asm
    WHERE asm.report_id = :report_id AND asm.personnel_id = :personnel_id
    LIMIT 1
');
$stmt->execute([
    ':report_id' => $reportId,
    ':personnel_id' => (int)$user['id'],
]);
$assignment = $stmt->fetch();
if (!$assignment) {
    Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
}

$update = DB::pdo()->prepare('
    UPDATE assignments
    SET
        progress_status = :progress_status,
        progress_note = :progress_note,
        progress_updated_at = datetime(\'now\')
    WHERE report_id = :report_id AND personnel_id = :personnel_id
');
$oldProgress = (string)($assignment['progress_status'] ?? 'not_started');

$update->execute([
    ':progress_status' => $progressStatus,
    ':progress_note' => $progressNote !== '' ? $progressNote : null,
    ':report_id' => $reportId,
    ':personnel_id' => (int)$user['id'],
]);

ActivityLog::write((int)$user['id'], 'assignment_progress_changed', 'assignment', $reportId, [
    'old_progress_status' => $oldProgress,
    'new_progress_status' => $progressStatus,
]);

$fetch = DB::pdo()->prepare('
    SELECT
        report_id,
        personnel_id,
        assigned_at,
        progress_status AS assignment_progress_status,
        progress_note AS assignment_progress_note,
        progress_updated_at AS assignment_progress_updated_at
    FROM assignments
    WHERE report_id = :report_id AND personnel_id = :personnel_id
');
$fetch->execute([
    ':report_id' => $reportId,
    ':personnel_id' => (int)$user['id'],
]);
$item = $fetch->fetch();

Response::json(['ok' => true, 'item' => $item]);
