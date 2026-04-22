<?php

declare(strict_types=1);

// config.php
// Keep the app runnable under a subfolder (XAMPP) like:
// http://localhost/web_gamon/
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gamon/'); // MUST end with trailing slash
}

return [
    'app' => [
        // App name used in API responses and page titles.
        'name' => 'Waste Management and Reporting System',
        'base_url' => BASE_URL,
    ],
    'db' => [
        // SQLite file path. The file is created automatically if missing.
        'sqlite_path' => __DIR__ . '/../database/waste.db',
    ],
    'security' => [
        // Session cookie name.
        'session_name' => 'waste_mgmt_session',
    ],
];

