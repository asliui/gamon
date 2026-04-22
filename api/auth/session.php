<?php

declare(strict_types=1);

// api/auth/session.php
// Returns current session user (if logged in).

require_once __DIR__ . '/../../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
\WebGamon\Core\Response::json([
    'ok' => true,
    'user' => $user,
]);

