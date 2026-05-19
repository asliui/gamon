<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

Auth::requireRole('admin');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['id'] = Validator::int($data, 'id');
$errors['name'] = Validator::requiredString($data, 'name', 2, 50);
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$id = (int)$data['id'];
$name = trim((string)$data['name']);

$stmt = DB::pdo()->prepare('UPDATE categories SET name = :name WHERE id = :id');
try {
    $stmt->execute([':name' => $name, ':id' => $id]);
} catch (\Throwable $e) {
    Response::json(['ok' => false, 'error' => 'A category with this name already exists.'], 409);
}

if ($stmt->rowCount() === 0) {
    Response::json(['ok' => false, 'error' => 'Category not found'], 404);
}

Response::json(['ok' => true]);
