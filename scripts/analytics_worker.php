<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\Auth;

if ($argc < 3) {
    fwrite(STDERR, "Usage: analytics_worker.php <user_id> <api_path>\n");
    exit(2);
}

$userId = (int)$argv[1];
$rawPath = ltrim($argv[2], '/');
$queryString = '';
if (str_contains($rawPath, '?')) {
    [$rawPath, $queryString] = explode('?', $rawPath, 2);
}
$path = $rawPath;
if ($queryString !== '') {
    parse_str($queryString, $parsed);
    if (is_array($parsed)) {
        $_GET = array_merge($_GET ?? [], $parsed);
    }
}
$file = __DIR__ . '/../' . $path;
if (!is_file($file)) {
    fwrite(STDERR, "Not found: $path\n");
    exit(2);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
Auth::login($userId);
include $file;
