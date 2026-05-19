<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Report priority values (separate from report workflow status).
 */
final class ReportPriority
{
    public const ALL = ['low', 'medium', 'high', 'critical'];

    public const DEFAULT = 'medium';

    /**
     * @return string|null Valid priority or null if invalid.
     */
    public static function normalize(mixed $raw, string $default = self::DEFAULT): ?string
    {
        $value = strtolower(trim((string)$raw));
        if ($value === '') {
            return $default;
        }
        return self::isValid($value) ? $value : null;
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::ALL, true);
    }

    public static function assertValid(string $value): void
    {
        if (!self::isValid($value)) {
            Response::json(['ok' => false, 'error' => 'Invalid priority'], 422);
        }
    }
}
