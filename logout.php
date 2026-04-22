<?php

declare(strict_types=1);

// logout.php
// Logs out the user (session) and redirects to home.

require_once __DIR__ . '/core/bootstrap.php';

\WebGamon\Core\Auth::logout();
redirect(base_url());

