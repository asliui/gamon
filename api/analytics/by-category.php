<?php

declare(strict_types=1);

// api/analytics/by-category.php
// Placeholder: counts by category.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$stmt = DB::pdo()->query('
  SELECT c.name AS category, COUNT(r.id) AS count
  FROM categories c
  LEFT JOIN reports r ON r.category_id = c.id
  GROUP BY c.id
  ORDER BY count DESC
');

Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);

