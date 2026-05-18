<?php

declare(strict_types=1);

// api/users/delete.php — Admin soft-deletes a user account.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\UserAccount;
use WebGamon\Core\Validator;

$currentUser = Auth::requireRole('admin');
Csrf::verify();

$data = Response::readJsonBody();

$errors = [];
$errors['user_id'] = Validator::int($data, 'user_id');
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$targetId = (int)$data['user_id'];
$confirm = trim((string)($data['confirm'] ?? ''));

$stmt = DB::pdo()->prepare('SELECT id, email, role, is_deleted FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $targetId]);
$target = $stmt->fetch();

if (!$target || (int)($target['is_deleted'] ?? 0) === 1) {
    Response::json(['ok' => false, 'error' => 'User not found'], 404);
}

UserAccount::assertNotLastAdmin($target);

$isSelf = (int)$currentUser['id'] === $targetId;

if ($isSelf) {
    if (strcasecmp($confirm, (string)$target['email']) !== 0) {
        Response::json(['ok' => false, 'error' => 'Type your exact email address to delete your own admin account'], 422);
    }
} elseif ($confirm !== 'DELETE') {
    Response::json(['ok' => false, 'error' => 'Type DELETE to confirm account removal'], 422);
}

if (!UserAccount::softDelete($targetId)) {
    Response::json(['ok' => false, 'error' => 'User could not be deleted'], 409);
}

if ($isSelf) {
    Auth::logout();
    Response::json([
        'ok' => true,
        'deleted_user_id' => $targetId,
        'redirect' => 'login.php?account_deleted=1',
        'message' => 'Your account has been deleted. Please log in with another account.',
    ]);
}

Response::json(['ok' => true, 'deleted_user_id' => $targetId]);
