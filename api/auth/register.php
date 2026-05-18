<?php

declare(strict_types=1);

// api/auth/register.php
// Creates a new user and logs them in (session).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\UserAccount;
use WebGamon\Core\Validator;

Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['name'] = Validator::requiredString($data, 'name', 2, 100);
$errors['email'] = Validator::email($data, 'email');
$errors['password'] = Validator::requiredString($data, 'password', 8, 200);

// Public registration always creates citizens; role changes are admin-only.
$role = 'citizen';

$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$name = trim((string)$data['name']);
$email = trim((string)$data['email']);
$password = (string)$data['password'];

if (UserAccount::isEmailTakenByActiveUser($email)) {
    Response::json(['ok' => false, 'error' => 'Email already exists'], 409);
}

$stmt = DB::pdo()->prepare('
  INSERT INTO users (name, email, password_hash, role)
  VALUES (:name, :email, :password_hash, :role)
');

try {
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);
} catch (\Throwable $e) {
    Response::json(['ok' => false, 'error' => 'Email already exists (or DB error)'], 409);
}

$userId = (int)DB::pdo()->lastInsertId();
\WebGamon\Core\Auth::login($userId);

Response::json([
    'ok' => true,
    'user' => \WebGamon\Core\Auth::user(),
]);

