<?php

declare(strict_types=1);

// api/analytics/by-area.php
// Placeholder: counts by area.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$stmt = DB::pdo()->query('
  SELECT a.name AS area, COUNT(r.id) AS count
  FROM areas a
  LEFT JOIN reports r ON r.area_id = a.id
  GROUP BY a.id
  ORDER BY count DESC
');

Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);

