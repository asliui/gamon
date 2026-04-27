<?php

declare(strict_types=1);

// api/reports/create.php
// Citizen creates a new waste report, optionally uploading an image.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$user = Auth::requireRole('citizen', 'admin', 'personnel');

// Since we use FormData (multipart) for file uploads, we read from $_POST instead of JSON Body
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

// Basic referential checks
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

// --- SECURE NATIVE PHP IMAGE UPLOAD LOGIC ---
$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
    $originalName = basename($_FILES['image']['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Restrict allowed extensions for security
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        // Create a unique filename to prevent overwriting existing files
        $newFileName = uniqid('gamon_', true) . '.' . $extension;
        $destinationFolder = __DIR__ . '/../../uploads/';
        
        // Ensure the uploads directory exists
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0777, true);
        }

        $destination = $destinationFolder . $newFileName;
        
        // Move the uploaded file from temporary storage to the uploads folder
        if (move_uploaded_file($tmpName, $destination)) {
            // Save the relative path for the database
            $imagePath = 'uploads/' . $newFileName;
        }
    }
}
// ---------------------------------------------

$stmt = DB::pdo()->prepare('
  INSERT INTO reports (citizen_id, category_id, area_id, description, image_path, status)
  VALUES (:citizen_id, :category_id, :area_id, :description, :image_path, :status)
');

$stmt->execute([
    ':citizen_id' => (int)$user['id'],
    ':category_id' => $categoryId,
    ':area_id' => $areaId,
    ':description' => $description,
    ':image_path' => $imagePath, // This will be null if no image was uploaded
    ':status' => 'open',
]);

$reportId = (int)DB::pdo()->lastInsertId();
Response::json(['ok' => true, 'report_id' => $reportId], 201);