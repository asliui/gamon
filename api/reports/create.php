<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\ActivityLog;
use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\ReportPriority;
use WebGamon\Core\SLA;
use WebGamon\Core\Response;
use WebGamon\Core\Upload;
use WebGamon\Core\Validator;

$user = Auth::requireRole('citizen', 'admin', 'personnel');
Csrf::verify();

$config = require __DIR__ . '/../../config/config.php';
$data = $_POST;

$errors = [];
$errors['category_id'] = Validator::int(['category_id' => (int)($data['category_id'] ?? 0)], 'category_id');
$errors['area_id'] = Validator::int(['area_id' => (int)($data['area_id'] ?? 0)], 'area_id');
$errors['description'] = Validator::requiredString($data, 'description', 5, 2000);
$errors = array_filter($errors, fn($v) => $v !== null);

if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$priority = ReportPriority::normalize($data['priority'] ?? ReportPriority::DEFAULT);
if ($priority === null) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => ['priority' => 'Invalid value.']], 422);
}

$categoryId = (int)$data['category_id'];
$areaId = (int)$data['area_id'];
$description = trim((string)$data['description']);

$cat = DB::pdo()->prepare('SELECT id FROM categories WHERE id = :id');
$cat->execute([':id' => $categoryId]);
if (!$cat->fetch()) {
    Response::json(['ok' => false, 'error' => 'Unknown category'], 400);
}

$area = DB::pdo()->prepare('SELECT id FROM areas WHERE id = :id');
$area->execute([':id' => $areaId]);
if (!$area->fetch()) {
    Response::json(['ok' => false, 'error' => 'Unknown area'], 400);
}

$imagePath = null;
if (isset($_FILES['image'])) {
    $imagePath = Upload::storeReportImage($_FILES['image'], $config);
}

$createdAt = gmdate('Y-m-d H:i:s');
$dueAt = SLA::calculateDueAt($priority, $createdAt);

$stmt = DB::pdo()->prepare('
  INSERT INTO reports (citizen_id, category_id, area_id, description, image_path, status, priority, due_at, created_at, updated_at)
  VALUES (:citizen_id, :category_id, :area_id, :description, :image_path, :status, :priority, :due_at, :created_at, :updated_at)
');

$stmt->execute([
    ':citizen_id' => (int)$user['id'],
    ':category_id' => $categoryId,
    ':area_id' => $areaId,
    ':description' => $description,
    ':image_path' => $imagePath,
    ':status' => 'open',
    ':priority' => $priority,
    ':due_at' => $dueAt,
    ':created_at' => $createdAt,
    ':updated_at' => $createdAt,
]);

$reportId = (int)DB::pdo()->lastInsertId();

ActivityLog::write((int)$user['id'], 'report_created', 'report', $reportId, [
    'priority' => $priority,
    'category_id' => $categoryId,
    'area_id' => $areaId,
]);

Response::json(['ok' => true, 'report_id' => $reportId, 'priority' => $priority], 201);
