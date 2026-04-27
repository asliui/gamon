<?php

declare(strict_types=1);

// api/areas/create.php
// Creates a new area/district. Admin only.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

Auth::requireRole('admin');
$data = Response::readJsonBody();

$errors = [];
$errors['name'] = Validator::requiredString($data, 'name', 2, 50);
$errors = array_filter($errors, fn($v) => $v !== null);

if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$name = trim((string)$data['name']);

try {
    $stmt = DB::pdo()->prepare('INSERT INTO areas (name) VALUES (:name)');
    $stmt->execute([':name' => $name]);
    Response::json(['ok' => true, 'id' => (int)DB::pdo()->lastInsertId()]);
} catch (\Throwable $e) {
    // If the area name already exists (UNIQUE constraint)
    Response::json(['ok' => false, 'error' => 'This area already exists.'], 409);
}