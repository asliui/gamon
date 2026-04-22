<?php

declare(strict_types=1);

// api/analytics/cleanest-dirtiest.php
// Minimal: top/bottom areas by open reports.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$stmt = DB::pdo()->query("
  SELECT a.name AS area, SUM(CASE WHEN r.status != 'resolved' THEN 1 ELSE 0 END) AS open_count
  FROM areas a
  LEFT JOIN reports r ON r.area_id = a.id
  GROUP BY a.id
  ORDER BY open_count DESC
");
$rows = $stmt->fetchAll();

Response::json([
    'ok' => true,
    'dirtiest' => array_slice($rows, 0, 3),
    'cleanest' => array_slice(array_reverse($rows), 0, 3),
]);

