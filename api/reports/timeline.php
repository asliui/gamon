<?php

declare(strict_types=1);

// api/reports/timeline.php — Activity timeline for a single report (RBAC).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

$user = Auth::requireLogin();
$reportId = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if ($reportId <= 0) {
    Response::json(['ok' => false, 'error' => 'Missing report_id'], 400);
}

$reportStmt = DB::pdo()->prepare('
  SELECT r.id, r.citizen_id, r.status, r.is_deleted, asm.personnel_id
  FROM reports r
  LEFT JOIN assignments asm ON asm.report_id = r.id
  WHERE r.id = :id AND r.is_deleted = 0
');
$reportStmt->execute([':id' => $reportId]);
$report = $reportStmt->fetch();
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

$stmt = DB::pdo()->prepare('
  SELECT
    al.id,
    al.action,
    al.entity_type,
    al.entity_id,
    al.details,
    al.created_at,
    u.name AS actor_name,
    u.email AS actor_email
  FROM activity_logs al
  LEFT JOIN users u ON u.id = al.actor_user_id
  WHERE (
    (al.entity_type = \'report\' AND al.entity_id = :report_id)
    OR (al.entity_type = \'assignment\' AND al.entity_id = :report_id_assign)
  )
  ORDER BY al.created_at ASC, al.id ASC
');
$stmt->execute([
    ':report_id' => $reportId,
    ':report_id_assign' => $reportId,
]);

$items = $stmt->fetchAll();
foreach ($items as &$item) {
    $item['id'] = (int)$item['id'];
}
unset($item);

Response::json(['ok' => true, 'items' => $items]);
