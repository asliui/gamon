<?php

declare(strict_types=1);

// api/reports/create.php — Create a waste report (multipart; citizen by default).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
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

$stmt = DB::pdo()->prepare('
  INSERT INTO reports (citizen_id, category_id, area_id, description, image_path, status)
  VALUES (:citizen_id, :category_id, :area_id, :description, :image_path, :status)
');

$stmt->execute([
    ':citizen_id' => (int)$user['id'],
    ':category_id' => $categoryId,
    ':area_id' => $areaId,
    ':description' => $description,
    ':image_path' => $imagePath,
    ':status' => 'open',
]);

$reportId = (int)DB::pdo()->lastInsertId();
Response::json(['ok' => true, 'report_id' => $reportId], 201);
