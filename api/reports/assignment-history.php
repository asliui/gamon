<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\AssignmentHistory;
use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$reportId = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if ($reportId <= 0) {
    Response::json(['ok' => false, 'error' => 'Missing report_id'], 400);
}

$check = DB::pdo()->prepare('SELECT id FROM reports WHERE id = :id AND is_deleted = 0');
$check->execute([':id' => $reportId]);
if (!$check->fetch()) {
    Response::json(['ok' => false, 'error' => 'Report not found'], 404);
}

Response::json(['ok' => true, 'items' => AssignmentHistory::listForReport($reportId)]);
