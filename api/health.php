<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

\WebGamon\Core\Response::json([
    'ok' => true,
    'service' => 'api',
    'time' => gmdate('c'),
]);

