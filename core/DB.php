<?php

declare(strict_types=1);

namespace WebGamon\Core;

use PDO;
use PDOException;

/**
 * DB.php
 * Minimal PDO (SQLite) singleton. Auto-migrates + seeds on first run.
 */
final class DB
{
    private static ?PDO $pdo = null;
    private static bool $migrated = false;

    public static function init(array $config): void
    {
        if (self::$pdo) {
            return;
        }

        $path = (string)($config['db']['sqlite_path'] ?? '');
        if ($path === '') {
            throw new \RuntimeException('Missing sqlite_path in config.');
        }

        $isNew = !file_exists($path);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        try {
            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('DB connection failed: ' . $e->getMessage(), 0, $e);
        }

        self::pdo()->exec('PRAGMA foreign_keys = ON;');
        self::migrate();

        if ($isNew) {
            self::seed();
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException('DB not initialized.');
        }
        return self::$pdo;
    }

    private static function migrate(): void
    {
        if (self::$migrated) {
            return;
        }
        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        if ($sql === false) {
            throw new \RuntimeException('Missing database/schema.sql');
        }
        self::pdo()->exec($sql);
        self::$migrated = true;
    }

    private static function seed(): void
    {
        $seedPath = __DIR__ . '/../database/seed.sql';
        if (!file_exists($seedPath)) {
            return;
        }
        $sql = file_get_contents($seedPath);
        if ($sql === false) {
            return;
        }
        self::pdo()->exec($sql);
    }
}

