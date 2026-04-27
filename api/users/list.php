<?php

declare(strict_types=1);

// api/users/list.php
// Returns a list of all users. Admin only.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

// SECURITY: Only admins can view the user list
Auth::requireRole('admin');

$stmt = DB::pdo()->query('
    SELECT id, name, email, role, created_at 
    FROM users 
    ORDER BY id DESC
');

Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);