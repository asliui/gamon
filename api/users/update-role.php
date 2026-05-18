<?php

declare(strict_types=1);

// api/users/update-role.php
// Updates a user's role. Admin only.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$currentUser = Auth::requireRole('admin');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['user_id'] = Validator::int($data, 'user_id');
$errors['role'] = Validator::oneOf($data, 'role', ['admin', 'citizen', 'personnel']);
$errors = array_filter($errors, fn($v) => $v !== null);

if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$targetUserId = (int)$data['user_id'];
$newRole = (string)$data['role'];

// Prevent admin from accidentally demoting themselves
if ($targetUserId === (int)$currentUser['id'] && $newRole !== 'admin') {
    Response::json(['ok' => false, 'error' => 'You cannot change your own admin role.'], 403);
}

$stmt = DB::pdo()->prepare('UPDATE users SET role = :role WHERE id = :id AND is_deleted = 0');
$stmt->execute([
    ':role' => $newRole,
    ':id' => $targetUserId,
]);

if ($stmt->rowCount() === 0) {
    Response::json(['ok' => false, 'error' => 'User not found'], 404);
}

Response::json(['ok' => true]);