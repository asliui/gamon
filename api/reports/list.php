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

$user = Auth::requireLogin();

$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
$status = isset($_GET['status']) ? (string)$_GET['status'] : null;

$params = [':limit' => $limit];
$where = [];

if ($status !== null && $status !== '') {
    $where[] = 'r.status = :status';
    $params[':status'] = $status;
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
    u.email AS citizen_email
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
    if ($k === ':limit' || $k === ':citizen_id' || $k === ':personnel_id') {
        $type = \PDO::PARAM_INT;
    }
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();

Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);