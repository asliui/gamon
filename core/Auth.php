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
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = DB::pdo()->prepare('SELECT id, email, name, role, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
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
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

