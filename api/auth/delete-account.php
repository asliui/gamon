<?php

declare(strict_types=1);

// api/auth/delete-account.php — Logged-in user soft-deletes their own account.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\Response;
use WebGamon\Core\UserAccount;

$user = Auth::requireLogin();
Csrf::verify();

$data = Response::readJsonBody();
$confirm = trim((string)($data['confirm'] ?? ''));

if ($confirm !== 'DELETE MY ACCOUNT') {
    Response::json(['ok' => false, 'error' => 'Type DELETE MY ACCOUNT to confirm'], 422);
}

UserAccount::assertNotLastAdmin($user);

if (!UserAccount::softDelete((int)$user['id'])) {
    Response::json(['ok' => false, 'error' => 'Account could not be deleted'], 409);
}

Auth::logout();

Response::json([
    'ok' => true,
    'redirect' => 'login.php?account_deleted=1',
    'message' => 'Your account has been deleted. Please log in with another account.',
]);
