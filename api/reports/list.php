<?php

declare(strict_types=1);

// api/reports/list.php
// Lists reports:
// - citizen: only own reports
// - personnel/admin: all reports, or filtered by assigned_to=me

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\UserAccount;

$user = Auth::requireLogin();

$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
$status = isset($_GET['status']) ? (string)$_GET['status'] : null;
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$areaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : null;

$params = [':limit' => $limit];
$where = [];

if ($status !== null && $status !== '') {
    $where[] = 'r.status = :status';
    $params[':status'] = $status;
}

if ($categoryId !== null && $categoryId > 0) {
    $where[] = 'r.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}

if ($areaId !== null && $areaId > 0) {
    $where[] = 'r.area_id = :area_id';
    $params[':area_id'] = $areaId;
}

if ($user['role'] === 'citizen') {
    $where[] = 'r.citizen_id = :citizen_id';
    $params[':citizen_id'] = (int)$user['id'];
}

$joinAssignments = '';
if (isset($_GET['assigned_to']) && $_GET['assigned_to'] === 'me' && in_array($user['role'], ['personnel', 'admin'])) {
    $joinAssignments = ' JOIN assignments asm ON asm.report_id = r.id ';
    $where[] = 'asm.personnel_id = :personnel_id';
    $params[':personnel_id'] = (int)$user['id'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
  SELECT
    r.id,
    r.description,
    r.status,
    r.created_at,
    c.name AS category,
    a.name AS area,
    u.email AS citizen_email,
    u.is_deleted AS citizen_is_deleted
  FROM reports r
  JOIN categories c ON c.id = r.category_id
  JOIN areas a ON a.id = r.area_id
  JOIN users u ON u.id = r.citizen_id
  $joinAssignments
  $whereSql
  ORDER BY r.id DESC
  LIMIT :limit
";

$stmt = DB::pdo()->prepare($sql);
foreach ($params as $k => $v) {
    $type = \PDO::PARAM_STR;
    if ($k === ':limit' || $k === ':citizen_id' || $k === ':personnel_id' || $k === ':category_id' || $k === ':area_id') {
        $type = \PDO::PARAM_INT;
    }
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();

$items = $stmt->fetchAll();
foreach ($items as &$item) {
    UserAccount::maskListCitizenEmail($item);
}
unset($item);

Response::json(['ok' => true, 'items' => $items]);