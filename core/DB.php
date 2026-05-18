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
        self::patchUsersSoftDelete();
        self::$migrated = true;
    }

    /** Adds soft-delete columns to existing SQLite databases. */
    private static function patchUsersSoftDelete(): void
    {
        $cols = self::pdo()->query('PRAGMA table_info(users)')->fetchAll();
        $names = array_column($cols, 'name');

        if (!in_array('is_deleted', $names, true)) {
            self::pdo()->exec('ALTER TABLE users ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0');
        }
        if (!in_array('deleted_at', $names, true)) {
            self::pdo()->exec('ALTER TABLE users ADD COLUMN deleted_at TEXT NULL');
        }

        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_deleted)');

        // One-time style patch: free emails from already soft-deleted rows (pre-anonymize fix).
        self::pdo()->exec("
            UPDATE users
            SET
                name = 'Deleted user',
                email = 'deleted_' || id || '_' || strftime('%s', 'now') || '@deleted.local'
            WHERE is_deleted = 1
              AND (email NOT LIKE 'deleted_%@deleted.local' OR email IS NULL)
        ");
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

