<?php

declare(strict_types=1);

// api/admin/activity-log.php — Paginated activity log for admins.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$entityType = isset($_GET['entity_type']) ? trim((string)$_GET['entity_type']) : '';
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$hasPerPage = isset($_GET['per_page']);
$perPage = $hasPerPage ? (int)$_GET['per_page'] : 20;

if ($perPage <= 0 && !$hasPerPage) {
    $perPage = 20;
} elseif ($perPage < 5) {
    $perPage = 5;
}
if ($perPage > 100) {
    $perPage = 100;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $perPage;

$params = [];
$where = ['1 = 1'];

if ($action !== '') {
    $where[] = 'al.action = :action';
    $params[':action'] = $action;
}

if ($entityType !== '') {
    $where[] = 'al.entity_type = :entity_type';
    $params[':entity_type'] = $entityType;
}

if ($search !== '') {
    $safeSearch = str_replace(['%', '_'], '', $search);
    if ($safeSearch !== '') {
        $where[] = '(
            al.details LIKE :q
            OR u.name LIKE :q
            OR u.email LIKE :q
            OR al.action LIKE :q
        )';
        $params[':q'] = '%' . $safeSearch . '%';
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$fromSql = "
  FROM activity_logs al
  LEFT JOIN users u ON u.id = al.actor_user_id
  $whereSql
";

$countSql = 'SELECT COUNT(*) ' . $fromSql;
$countStmt = DB::pdo()->prepare($countSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();
$totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;

$sql = "
  SELECT
    al.id,
    al.actor_user_id,
    al.action,
    al.entity_type,
    al.entity_id,
    al.details,
    al.created_at,
    u.name AS actor_name,
    u.email AS actor_email
  $fromSql
  ORDER BY al.created_at DESC, al.id DESC
  LIMIT :limit OFFSET :offset
";

$listParams = $params;
$listParams[':limit'] = $perPage;
$listParams[':offset'] = $offset;

$stmt = DB::pdo()->prepare($sql);
foreach ($listParams as $k => $v) {
    $type = in_array($k, [':limit', ':offset'], true) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();

$items = $stmt->fetchAll();
foreach ($items as &$item) {
    if (isset($item['actor_user_id'])) {
        $item['actor_user_id'] = $item['actor_user_id'] !== null ? (int)$item['actor_user_id'] : null;
    }
    if (isset($item['entity_id'])) {
        $item['entity_id'] = $item['entity_id'] !== null ? (int)$item['entity_id'] : null;
    }
    if (isset($item['id'])) {
        $item['id'] = (int)$item['id'];
    }
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
