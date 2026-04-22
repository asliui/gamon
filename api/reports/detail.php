<?php

declare(strict_types=1);

// api/reports/detail.php
// Placeholder: returns a single report by id.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

$user = Auth::requireLogin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    Response::json(['ok' => false, 'error' => 'Missing id'], 400);
}

$stmt = DB::pdo()->prepare('
  SELECT r.*, c.name AS category, a.name AS area
  FROM reports r
  JOIN categories c ON c.id = r.category_id
  JOIN areas a ON a.id = r.area_id
  WHERE r.id = :id
');
$stmt->execute([':id' => $id]);
$report = $stmt->fetch();
if (!$report) {
    Response::json(['ok' => false, 'error' => 'Not found'], 404);
}

if ($user['role'] === 'citizen' && (int)$report['citizen_id'] !== (int)$user['id']) {
    Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
}

Response::json(['ok' => true, 'item' => $report]);

