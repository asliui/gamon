<?php

declare(strict_types=1);

// api/auth/login.php
// Logs in a user by email/password (session-based).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$data = Response::readJsonBody();

$errors = [];
$errors['email'] = Validator::email($data, 'email');
$errors['password'] = Validator::requiredString($data, 'password', 1, 200);
$errors = array_filter($errors, fn($v) => $v !== null);

if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$email = trim((string)$data['email']);
$password = (string)$data['password'];

$stmt = DB::pdo()->prepare('SELECT id, password_hash FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, (string)$row['password_hash'])) {
    Response::json(['ok' => false, 'error' => 'Invalid credentials'], 401);
}

\WebGamon\Core\Auth::login((int)$row['id']);
Response::json(['ok' => true, 'user' => \WebGamon\Core\Auth::user()]);

