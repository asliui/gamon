<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Auth.php
 * Session-based auth helper for API endpoints and pages.
 */
final class Auth
{
    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = DB::pdo()->prepare('
            SELECT id, email, name, role, created_at
            FROM users
            WHERE id = :id AND is_deleted = 0
        ');
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            // Clear stale login only — keep session alive for CSRF on public pages.
            unset($_SESSION['user_id']);
            return null;
        }

        return $user;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        return $user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        return $user;
    }

    public static function login(int $userId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $csrf = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
            ? $_SESSION['csrf_token']
            : null;

        session_regenerate_id(true);
        $_SESSION = ['user_id' => $userId];

        if ($csrf !== null && $csrf !== '') {
            $_SESSION['csrf_token'] = $csrf;
        }
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?: '',
                'secure' => (bool)$params['secure'],
                'httponly' => (bool)$params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        unset($_SESSION);
    }
}
