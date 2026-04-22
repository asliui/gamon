<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Response.php
 * Small JSON response helper for API endpoints.
 */
final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::json(['ok' => false, 'error' => 'Invalid JSON body'], 400);
        }
        return $data;
    }
}

