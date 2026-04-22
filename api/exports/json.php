<?php

declare(strict_types=1);

// api/exports/json.php
// Exports reports as JSON (admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;

Auth::requireRole('admin');

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="reports.json"');

$stmt = DB::pdo()->query('SELECT * FROM reports ORDER BY id DESC');
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

