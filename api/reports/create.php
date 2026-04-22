<?php

declare(strict_types=1);

// api/reports/create.php
// Citizen creates a new waste report.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$user = Auth::requireRole('citizen', 'admin', 'personnel');

$data = Response::readJsonBody();

$errors = [];
$errors['category_id'] = Validator::int($data, 'category_id');
$errors['area_id'] = Validator::int($data, 'area_id');
$errors['description'] = Validator::requiredString($data, 'description', 5, 2000);
$errors = array_filter($errors, fn($v) => $v !== null);

if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$categoryId = (int)$data['category_id'];
$areaId = (int)$data['area_id'];
$description = trim((string)$data['description']);

// Basic referential checks.
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

$stmt = DB::pdo()->prepare('
  INSERT INTO reports (citizen_id, category_id, area_id, description, status)
  VALUES (:citizen_id, :category_id, :area_id, :description, :status)
');
$stmt->execute([
    ':citizen_id' => (int)$user['id'],
    ':category_id' => $categoryId,
    ':area_id' => $areaId,
    ':description' => $description,
    ':status' => 'open',
]);

$reportId = (int)DB::pdo()->lastInsertId();
Response::json(['ok' => true, 'report_id' => $reportId], 201);

