<?php

declare(strict_types=1);

namespace WebGamon\Core;

use PDO;

/**
 * Soft-delete and account lifecycle helpers.
 */
final class UserAccount
{
    public static function countActiveAdmins(PDO $pdo = null): int
    {
        $pdo = $pdo ?? DB::pdo();
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_deleted = 0");
        return (int)$stmt->fetch()['c'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findActiveById(int $id, PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? DB::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, role, is_deleted FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row || (int)($row['is_deleted'] ?? 0) === 1) {
            return null;
        }
        return $row;
    }

    public static function softDelete(int $userId, PDO $pdo = null): bool
    {
        $pdo = $pdo ?? DB::pdo();
        // Anonymize email so the original address can be used to register again.
        // Keep the row for report history (citizen_id FK); display as "Deleted user" in UI.
        $stmt = $pdo->prepare("
            UPDATE users
            SET
                is_deleted = 1,
                deleted_at = datetime('now'),
                name = 'Deleted user',
                email = 'deleted_' || id || '_' || strftime('%s', 'now') || '@deleted.local'
            WHERE id = :id AND is_deleted = 0
        ");
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function isEmailTakenByActiveUser(string $email, PDO $pdo = null): bool
    {
        $pdo = $pdo ?? DB::pdo();
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND is_deleted = 0 LIMIT 1');
        $stmt->execute([':email' => trim($email)]);
        return (bool)$stmt->fetchColumn();
    }

    public static function assertNotLastAdmin(array $target): void
    {
        if (($target['role'] ?? '') === 'admin' && self::countActiveAdmins() <= 1) {
            Response::json(['ok' => false, 'error' => 'Cannot delete the last active admin account'], 403);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function maskCitizenFields(array &$row): void
    {
        if ((int)($row['citizen_is_deleted'] ?? 0) === 1) {
            $row['citizen_name'] = 'Deleted user';
            $row['citizen_email'] = '';
        }
        unset($row['citizen_is_deleted']);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function maskPersonnelFields(array &$row): void
    {
        if ((int)($row['personnel_is_deleted'] ?? 0) === 1) {
            $row['personnel_name'] = 'Deleted user';
            $row['personnel_email'] = '';
        }
        unset($row['personnel_is_deleted']);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function maskListCitizenEmail(array &$row): void
    {
        if ((int)($row['citizen_is_deleted'] ?? 0) === 1) {
            $row['citizen_email'] = 'Deleted user';
        }
        unset($row['citizen_is_deleted']);
    }
}
