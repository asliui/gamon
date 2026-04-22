<?php

declare(strict_types=1);

// api/exports/html.php
// Exports a simple HTML table (admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;

Auth::requireRole('admin');

header('Content-Type: text/html; charset=utf-8');

$stmt = DB::pdo()->query('
  SELECT r.id, r.status, r.created_at, c.name AS category, a.name AS area
  FROM reports r
  JOIN categories c ON c.id = r.category_id
  JOIN areas a ON a.id = r.area_id
  ORDER BY r.id DESC
  LIMIT 500
');
$items = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Reports Export</title>
</head>
<body>
  <h1>Reports Export</h1>
  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>ID</th><th>Status</th><th>Category</th><th>Area</th><th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r): ?>
        <tr>
          <td><?= htmlspecialchars((string)$r['id']) ?></td>
          <td><?= htmlspecialchars((string)$r['status']) ?></td>
          <td><?= htmlspecialchars((string)$r['category']) ?></td>
          <td><?= htmlspecialchars((string)$r['area']) ?></td>
          <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>

