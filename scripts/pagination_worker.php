<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\Auth;

if ($argc < 3) {
    fwrite(STDERR, "Usage: pagination_worker.php <user_id> <json_query>\n");
    exit(2);
}

$userId = (int)$argv[1];
$query = json_decode($argv[2], true);
if (!is_array($query)) {
    fwrite(STDERR, "Invalid query JSON\n");
    exit(2);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
Auth::login($userId);
$_GET = $query;
include __DIR__ . '/../api/reports/list.php';
