<?php

declare(strict_types=1);

// api/reports/detail.php — Returns a single report by id (role-aware access).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\UserAccount;

$user = Auth::requireLogin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    Response::json(['ok' => false, 'error' => 'Missing id'], 400);
}

$stmt = DB::pdo()->prepare('
  SELECT
    r.*,
    c.name AS category,
    a.name AS area,
    uc.name AS citizen_name,
    uc.email AS citizen_email,
    uc.is_deleted AS citizen_is_deleted,
    asm.personnel_id,
    asm.assigned_at,
    asm.progress_status AS assignment_progress_status,
    asm.progress_note AS assignment_progress_note,
    asm.progress_updated_at AS assignment_progress_updated_at,
    up.name AS personnel_name,
    up.email AS personnel_email,
    up.is_deleted AS personnel_is_deleted
  FROM reports r
  JOIN categories c ON c.id = r.category_id
  JOIN areas a ON a.id = r.area_id
  JOIN users uc ON uc.id = r.citizen_id
  LEFT JOIN assignments asm ON asm.report_id = r.id
  LEFT JOIN users up ON up.id = asm.personnel_id
  WHERE r.id = :id AND r.is_deleted = 0
');
$stmt->execute([':id' => $id]);
$report = $stmt->fetch();
if (!$report) {
    Response::json(['ok' => false, 'error' => 'Not found'], 404);
}

if ($user['role'] === 'citizen' && (int)$report['citizen_id'] !== (int)$user['id']) {
    Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
}

if ($user['role'] === 'personnel') {
    $isOpen = ($report['status'] ?? '') === 'open';
    $assignedToMe = (int)($report['personnel_id'] ?? 0) === (int)$user['id'];

    if (!$isOpen && !$assignedToMe) {
        Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
    }
}

UserAccount::maskCitizenFields($report);
UserAccount::maskPersonnelFields($report);

Response::json(['ok' => true, 'item' => $report]);
