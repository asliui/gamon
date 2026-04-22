<?php

declare(strict_types=1);

// api/exports/csv.php
// Exports reports as CSV (admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;

Auth::requireRole('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reports.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'citizen_id', 'category_id', 'area_id', 'status', 'created_at']);

$stmt = DB::pdo()->query('SELECT id, citizen_id, category_id, area_id, status, created_at FROM reports ORDER BY id DESC');
foreach ($stmt->fetchAll() as $row) {
    fputcsv($out, $row);
}
fclose($out);

