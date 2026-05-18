<?php

declare(strict_types=1);

// api/auth/logout.php
// Destroys the current session.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Csrf;

Csrf::verify();
\WebGamon\Core\Auth::logout();
\WebGamon\Core\Response::json(['ok' => true]);

