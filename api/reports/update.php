<?php

declare(strict_types=1);

// api/reports/update.php — Admin updates report fields and status.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\ReportStatus;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

Auth::requireRole('admin');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['report_id'] = Validator::int($data, 'report_id');
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$reportId = (int)$data['report_id'];

$stmt = DB::pdo()->prepare('SELECT id, status, is_deleted FROM reports WHERE id = :id');
$stmt->execute([':id' => $reportId]);
$report = $stmt->fetch();
if (!$report || (int)($report['is_deleted'] ?? 0) === 1) {
    Response::json(['ok' => false, 'error' => 'Report not found'], 404);
}

$currentStatus = (string)$report['status'];
$updates = [];
$params = [':id' => $reportId];

if (isset($data['description'])) {
    $descErr = Validator::requiredString($data, 'description', 5, 2000);
    if ($descErr !== null) {
        Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => ['description' => $descErr]], 422);
    }
    $updates[] = 'description = :description';
    $params[':description'] = trim((string)$data['description']);
}

if (isset($data['category_id'])) {
    $catErr = Validator::int($data, 'category_id');
    if ($catErr !== null) {
        Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => ['category_id' => $catErr]], 422);
    }
    $categoryId = (int)$data['category_id'];
    $cat = DB::pdo()->prepare('SELECT id FROM categories WHERE id = :id');
    $cat->execute([':id' => $categoryId]);
    if (!$cat->fetch()) {
        Response::json(['ok' => false, 'error' => 'Unknown category'], 400);
    }
    $updates[] = 'category_id = :category_id';
    $params[':category_id'] = $categoryId;
}

if (isset($data['area_id'])) {
    $areaErr = Validator::int($data, 'area_id');
    if ($areaErr !== null) {
        Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => ['area_id' => $areaErr]], 422);
    }
    $areaId = (int)$data['area_id'];
    $area = DB::pdo()->prepare('SELECT id FROM areas WHERE id = :id');
    $area->execute([':id' => $areaId]);
    if (!$area->fetch()) {
        Response::json(['ok' => false, 'error' => 'Unknown area'], 400);
    }
    $updates[] = 'area_id = :area_id';
    $params[':area_id'] = $areaId;
}

if (isset($data['status'])) {
    $statusErr = Validator::oneOf($data, 'status', ['open', 'assigned', 'in_progress', 'resolved', 'rejected']);
    if ($statusErr !== null) {
        Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => ['status' => $statusErr]], 422);
    }
    $newStatus = (string)$data['status'];
    ReportStatus::assertCanTransition($currentStatus, $newStatus, true);
    $updates[] = 'status = :status';
    $params[':status'] = $newStatus;
}

if (!$updates) {
    Response::json(['ok' => false, 'error' => 'No fields to update'], 400);
}

$updates[] = "updated_at = datetime('now')";
$sql = 'UPDATE reports SET ' . implode(', ', $updates) . ' WHERE id = :id';
DB::pdo()->prepare($sql)->execute($params);

Response::json(['ok' => true]);
