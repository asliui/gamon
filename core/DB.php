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
    /** Bump when schema/patches change; skips heavy migrate on warm DB. */
    private const SCHEMA_VERSION = 7;

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
        self::pdo()->exec('PRAGMA journal_mode = WAL;');
        self::pdo()->exec('PRAGMA busy_timeout = 30000;');
        self::migrate();

        if ($isNew) {
            self::seed();
        }
    }

    /** Release the connection (e.g. before CLI subprocess tests that open SQLite again). */
    public static function disconnect(): void
    {
        self::$pdo = null;
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

        $current = (int)self::pdo()->query('PRAGMA user_version')->fetchColumn();
        if ($current >= self::SCHEMA_VERSION) {
            self::$migrated = true;
            return;
        }

        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        if ($sql === false) {
            throw new \RuntimeException('Missing database/schema.sql');
        }
        self::pdo()->exec($sql);
        self::patchUsersSoftDelete();
        self::patchReportsSoftDelete();
        self::patchReportsPriority();
        self::patchAssignmentHistory();
        self::patchAssignmentsProgress();
        self::patchActivityLogs();
        self::patchReportsSla();
        self::pdo()->exec('PRAGMA user_version = ' . self::SCHEMA_VERSION);
        self::$migrated = true;
    }

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

        $needsMask = self::pdo()->query("
            SELECT 1 FROM users
            WHERE is_deleted = 1
              AND (email NOT LIKE 'deleted_%@deleted.local' OR email IS NULL)
            LIMIT 1
        ")->fetchColumn();
        if ($needsMask) {
            self::pdo()->exec("
                UPDATE users
                SET
                    name = 'Deleted user',
                    email = 'deleted_' || id || '_' || strftime('%s', 'now') || '@deleted.local'
                WHERE is_deleted = 1
                  AND (email NOT LIKE 'deleted_%@deleted.local' OR email IS NULL)
            ");
        }
    }

    private static function patchReportsSoftDelete(): void
    {
        $cols = self::pdo()->query('PRAGMA table_info(reports)')->fetchAll();
        $names = array_column($cols, 'name');

        if (!in_array('is_deleted', $names, true)) {
            self::pdo()->exec('ALTER TABLE reports ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0');
        }
        if (!in_array('deleted_at', $names, true)) {
            self::pdo()->exec('ALTER TABLE reports ADD COLUMN deleted_at TEXT NULL');
        }

        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_reports_deleted ON reports(is_deleted)');
    }

    private static function patchReportsPriority(): void
    {
        $cols = self::pdo()->query('PRAGMA table_info(reports)')->fetchAll();
        $names = array_column($cols, 'name');

        if (!in_array('priority', $names, true)) {
            self::pdo()->exec("ALTER TABLE reports ADD COLUMN priority TEXT NOT NULL DEFAULT 'medium'");
        }

        self::pdo()->exec("
            UPDATE reports
            SET priority = 'medium'
            WHERE priority IS NULL OR priority = ''
        ");

        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_reports_priority ON reports(priority)');
    }

    private static function patchAssignmentHistory(): void
    {
        self::pdo()->exec('
            CREATE TABLE IF NOT EXISTS assignment_history (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              report_id INTEGER NOT NULL,
              old_personnel_id INTEGER NULL,
              new_personnel_id INTEGER NOT NULL,
              assigned_by INTEGER NOT NULL,
              assigned_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
              FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
              FOREIGN KEY (old_personnel_id) REFERENCES users(id) ON DELETE SET NULL,
              FOREIGN KEY (new_personnel_id) REFERENCES users(id) ON DELETE CASCADE,
              FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ');
        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_assignment_history_report ON assignment_history(report_id)');
    }

    private static function patchAssignmentsProgress(): void
    {
        $cols = self::pdo()->query('PRAGMA table_info(assignments)')->fetchAll();
        $names = array_column($cols, 'name');

        if (!in_array('progress_status', $names, true)) {
            self::pdo()->exec("
                ALTER TABLE assignments
                ADD COLUMN progress_status TEXT NOT NULL DEFAULT 'not_started'
            ");
        }
        if (!in_array('progress_note', $names, true)) {
            self::pdo()->exec('ALTER TABLE assignments ADD COLUMN progress_note TEXT NULL');
        }
        if (!in_array('progress_updated_at', $names, true)) {
            self::pdo()->exec('ALTER TABLE assignments ADD COLUMN progress_updated_at TEXT NULL');
        }

        self::pdo()->exec("
            UPDATE assignments
            SET progress_status = 'not_started'
            WHERE progress_status IS NULL OR progress_status = ''
        ");
    }

    private static function patchActivityLogs(): void
    {
        self::pdo()->exec('
            CREATE TABLE IF NOT EXISTS activity_logs (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              actor_user_id INTEGER NULL,
              action TEXT NOT NULL,
              entity_type TEXT NOT NULL,
              entity_id INTEGER NULL,
              details TEXT NULL,
              created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
              FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at DESC)');
        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action)');
        self::pdo()->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_entity_type ON activity_logs(entity_type)');
    }

    private static function patchReportsSla(): void
    {
        $cols = self::pdo()->query('PRAGMA table_info(reports)')->fetchAll();
        $names = array_column($cols, 'name');

        if (!in_array('due_at', $names, true)) {
            self::pdo()->exec('ALTER TABLE reports ADD COLUMN due_at TEXT NULL');
        }
        if (!in_array('resolved_at', $names, true)) {
            self::pdo()->exec('ALTER TABLE reports ADD COLUMN resolved_at TEXT NULL');
        }

        $rows = self::pdo()->query("
            SELECT id, priority, created_at
            FROM reports
            WHERE is_deleted = 0 AND (due_at IS NULL OR due_at = '')
        ")->fetchAll();

        $update = self::pdo()->prepare('UPDATE reports SET due_at = :due_at WHERE id = :id');
        foreach ($rows as $row) {
            $dueAt = SLA::calculateDueAt(
                (string)($row['priority'] ?? ReportPriority::DEFAULT),
                (string)($row['created_at'] ?? 'now')
            );
            $update->execute([':due_at' => $dueAt, ':id' => (int)$row['id']]);
        }

        self::pdo()->exec("
            UPDATE reports
            SET resolved_at = updated_at
            WHERE status = 'resolved'
              AND is_deleted = 0
              AND (resolved_at IS NULL OR resolved_at = '')
              AND updated_at IS NOT NULL
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
