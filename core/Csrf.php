<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Session-based CSRF tokens for state-changing requests.
 * Clients must send: X-CSRF-Token header.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /** @var array<string, mixed>|null */
    private static ?array $jsonBodyCache = null;

    /** @var bool */
    private static bool $jsonBodyRead = false;

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return '';
            }
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verify(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $sent = self::readHeaderToken();
        $expected = (string)($_SESSION[self::SESSION_KEY] ?? '');

        if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
            Response::json(['ok' => false, 'error' => 'Invalid or missing CSRF token'], 403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsonBody(): array
    {
        if (!self::$jsonBodyRead) {
            self::$jsonBodyRead = true;
            $raw = file_get_contents('php://input');
            if ($raw === false || trim($raw) === '') {
                self::$jsonBodyCache = [];
            } else {
                $decoded = json_decode($raw, true);
                self::$jsonBodyCache = is_array($decoded) ? $decoded : [];
            }
        }

        return self::$jsonBodyCache ?? [];
    }

    private static function readHeaderToken(): string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string)$name, 'X-CSRF-Token') === 0) {
                        $header = (string)$value;
                        break;
                    }
                }
            }
        }

        return is_string($header) ? trim($header) : '';
    }
}
