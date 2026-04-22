<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Validator.php
 * Tiny validation helpers. Return "field => error" map.
 */
final class Validator
{
    public static function requiredString(array $data, string $key, int $min = 1, int $max = 255): ?string
    {
        $value = trim((string)($data[$key] ?? ''));
        if ($value === '') {
            return 'Required.';
        }
        if (mb_strlen($value) < $min) {
            return 'Too short.';
        }
        if (mb_strlen($value) > $max) {
            return 'Too long.';
        }
        return null;
    }

    public static function email(array $data, string $key): ?string
    {
        $value = trim((string)($data[$key] ?? ''));
        if ($value === '') {
            return 'Required.';
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email.';
        }
        return null;
    }

    public static function oneOf(array $data, string $key, array $allowed): ?string
    {
        $value = (string)($data[$key] ?? '');
        if (!in_array($value, $allowed, true)) {
            return 'Invalid value.';
        }
        return null;
    }

    public static function int(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return 'Required.';
        }
        if (!is_int($data[$key]) && !ctype_digit((string)$data[$key])) {
            return 'Must be an integer.';
        }
        return null;
    }
}

