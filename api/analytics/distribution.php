<?php

declare(strict_types=1);

// api/analytics/distribution.php — Report distributions for admin charts (active reports only).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$pdo = DB::pdo();
$active = 'r.is_deleted = 0';

/**
 * @param list<string> $order
 * @param array<string, int> $counts
 * @return list<array{label: string, count: int}>
 */
function fixedDistribution(array $order, array $counts): array
{
    $rows = [];
    foreach ($order as $key) {
        $rows[] = ['label' => $key, 'count' => $counts[$key] ?? 0];
    }
    return $rows;
}

$statusOrder = ['open', 'assigned', 'in_progress', 'resolved'];
$priorityOrder = ['low', 'medium', 'high', 'critical'];

$statusCounts = [];
$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM reports r WHERE {$active} GROUP BY status");
foreach ($stmt->fetchAll() as $row) {
    $key = (string)($row['status'] ?? '');
    if ($key !== '') {
        $statusCounts[$key] = (int)$row['count'];
    }
}

$priorityCounts = [];
$stmt = $pdo->query("SELECT priority, COUNT(*) AS count FROM reports r WHERE {$active} GROUP BY priority");
foreach ($stmt->fetchAll() as $row) {
    $key = (string)($row['priority'] ?? '');
    if ($key !== '') {
        $priorityCounts[$key] = (int)$row['count'];
    }
}

$catStmt = $pdo->query("
  SELECT c.name AS label, COUNT(r.id) AS count
  FROM categories c
  INNER JOIN reports r ON r.category_id = c.id AND r.is_deleted = 0
  GROUP BY c.id
  HAVING count > 0
  ORDER BY count DESC, c.name ASC
");

$areaStmt = $pdo->query("
  SELECT a.name AS label, COUNT(r.id) AS count
  FROM areas a
  INNER JOIN reports r ON r.area_id = a.id AND r.is_deleted = 0
  GROUP BY a.id
  HAVING count > 0
  ORDER BY count DESC, a.name ASC
");

$categories = [];
foreach ($catStmt->fetchAll() as $row) {
    $categories[] = [
        'label' => (string)$row['label'],
        'count' => (int)$row['count'],
    ];
}

$areas = [];
foreach ($areaStmt->fetchAll() as $row) {
    $areas[] = [
        'label' => (string)$row['label'],
        'count' => (int)$row['count'],
    ];
}

Response::json([
    'ok' => true,
    'status' => fixedDistribution($statusOrder, $statusCounts),
    'priority' => fixedDistribution($priorityOrder, $priorityCounts),
    'categories' => $categories,
    'areas' => $areas,
]);
