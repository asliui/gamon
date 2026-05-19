<?php

declare(strict_types=1);

/**
 * CLI: Load demo seed data into an existing database (idempotent via INSERT OR IGNORE).
 * Usage: php scripts/seed_demo.php
 */

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;

$seedPath = __DIR__ . '/../database/seed.sql';
if (!file_exists($seedPath)) {
    fwrite(STDERR, "seed.sql not found.\n");
    exit(1);
}

$sql = file_get_contents($seedPath);
if ($sql === false) {
    fwrite(STDERR, "Could not read seed.sql.\n");
    exit(1);
}

DB::pdo()->exec($sql);
echo "Demo seed applied. Default password for demo accounts: Demo123!\n";
echo "Admin: asliuzar4@gmail.com\n";
