<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\ReportPriority;
use WebGamon\Core\Response;
use WebGamon\Core\SLA;
use WebGamon\Core\UserAccount;

$user = Auth::requireLogin();

$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$areaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;
$priority = isset($_GET['priority']) ? trim((string)$_GET['priority']) : '';
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$slaStatus = isset($_GET['sla_status']) ? trim((string)$_GET['sla_status']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$hasPerPage = isset($_GET['per_page']);
$perPage = $hasPerPage ? (int)$_GET['per_page'] : 0;

// Legacy: ?limit=N without per_page → treat as per_page (capped at 50)
if (!$hasPerPage && isset($_GET['limit'])) {
    $perPage = (int)$_GET['limit'];
}

if ($perPage <= 0 && !$hasPerPage) {
    $perPage = 10;
} elseif ($perPage < 5) {
    $perPage = 5;
}
if ($perPage > 50) {
    $perPage = 50;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $perPage;

$params = [];
$where = ['r.is_deleted = 0'];

if ($status !== '') {
    $where[] = 'r.status = :status';
    $params[':status'] = $status;
}

if ($categoryId > 0) {
    $where[] = 'r.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}

if ($areaId > 0) {
    $where[] = 'r.area_id = :area_id';
    $params[':area_id'] = $areaId;
}

if ($priority !== '') {
    if (!ReportPriority::isValid($priority)) {
        Response::json(['ok' => false, 'error' => 'Invalid priority'], 422);
    }
    $where[] = 'r.priority = :priority';
    $params[':priority'] = $priority;
}

if ($search !== '') {
    $safeSearch = str_replace(['%', '_'], '', $search);
    if ($safeSearch !== '') {
        $where[] = '(r.description LIKE :q OR CAST(r.id AS TEXT) LIKE :q_id)';
        $params[':q'] = '%' . $safeSearch . '%';
        $params[':q_id'] = '%' . $safeSearch . '%';
    }
}

if ($slaStatus !== '') {
    if ($user['role'] !== 'admin') {
        Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
    }
    $allowedSla = ['overdue', 'due_soon', 'on_time', 'resolved_late'];
    if (!in_array($slaStatus, $allowedSla, true)) {
        Response::json(['ok' => false, 'error' => 'Invalid sla_status'], 422);
    }
    $unresolved = "r.status NOT IN ('resolved', 'rejected')";
    switch ($slaStatus) {
        case 'overdue':
            $where[] = $unresolved . " AND r.due_at IS NOT NULL AND r.due_at != '' AND r.due_at < datetime('now')";
            break;
        case 'due_soon':
            $where[] = $unresolved . " AND r.due_at IS NOT NULL AND r.due_at != '' AND r.due_at >= datetime('now') AND r.due_at <= datetime('now', '+24 hours')";
            break;
        case 'on_time':
            $where[] = $unresolved . " AND r.due_at IS NOT NULL AND r.due_at != '' AND r.due_at > datetime('now', '+24 hours')";
            break;
        case 'resolved_late':
            $where[] = "r.status = 'resolved' AND r.resolved_at IS NOT NULL AND r.resolved_at != '' AND r.due_at IS NOT NULL AND r.due_at != '' AND r.resolved_at > r.due_at";
            break;
    }
}

if ($user['role'] === 'citizen') {
    $where[] = 'r.citizen_id = :citizen_id';
    $params[':citizen_id'] = (int)$user['id'];
}

if ($user['role'] === 'personnel' && (!isset($_GET['assigned_to']) || $_GET['assigned_to'] !== 'me')) {
    $where[] = "(r.status = 'open' OR EXISTS (
        SELECT 1 FROM assignments ax WHERE ax.report_id = r.id AND ax.personnel_id = :personnel_scope
    ))";
    $params[':personnel_scope'] = (int)$user['id'];
}

$joinAssignments = '';
if (isset($_GET['assigned_to']) && $_GET['assigned_to'] === 'me' && in_array($user['role'], ['personnel', 'admin'], true)) {
    $joinAssignments = ' JOIN assignments asm ON asm.report_id = r.id ';
    $where[] = 'asm.personnel_id = :personnel_id';
    $params[':personnel_id'] = (int)$user['id'];
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$fromSql = "
  FROM reports r
  JOIN categories c ON c.id = r.category_id
  JOIN areas a ON a.id = r.area_id
  JOIN users u ON u.id = r.citizen_id
  $joinAssignments
  $whereSql
";

$countSql = 'SELECT COUNT(*) AS cnt ' . $fromSql;
$countStmt = DB::pdo()->prepare($countSql);
foreach ($params as $k => $v) {
    $type = \PDO::PARAM_STR;
    if (in_array($k, [':citizen_id', ':personnel_id', ':personnel_scope', ':category_id', ':area_id'], true)) {
        $type = \PDO::PARAM_INT;
    }
    $countStmt->bindValue($k, $v, $type);
}
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

$totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;

$assignmentFields = '';
if ($joinAssignments !== '') {
    $assignmentFields = "
    asm.progress_status AS assignment_progress_status,
    asm.progress_note AS assignment_progress_note,
    asm.progress_updated_at AS assignment_progress_updated_at,";
}

$sql = "
  SELECT
    r.id,
    r.description,
    r.status,
    r.priority,
    r.due_at,
    r.resolved_at,
    r.created_at,
    c.name AS category,
    a.name AS area,
    u.email AS citizen_email,
    u.is_deleted AS citizen_is_deleted,
    {$assignmentFields}
    1 AS _row
  $fromSql
  ORDER BY r.id DESC
  LIMIT :limit OFFSET :offset
";

$listParams = $params;
$listParams[':limit'] = $perPage;
$listParams[':offset'] = $offset;

$stmt = DB::pdo()->prepare($sql);
foreach ($listParams as $k => $v) {
    $type = \PDO::PARAM_STR;
    if (in_array($k, [':limit', ':offset', ':citizen_id', ':personnel_id', ':personnel_scope', ':category_id', ':area_id'], true)) {
        $type = \PDO::PARAM_INT;
    }
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();

$items = $stmt->fetchAll();
foreach ($items as &$item) {
    UserAccount::maskListCitizenEmail($item);
    $item = SLA::enrichReport($item);
    unset($item['_row']);
}
unset($item);

Response::json([
    'ok' => true,
    'items' => $items,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages,
]);
