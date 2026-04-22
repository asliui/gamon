<?php

declare(strict_types=1);

/**
 * helpers.php
 * Small shared functions for pages.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $base = defined('BASE_URL') ? (string)BASE_URL : '/';
    if ($base === '') {
        $base = '/';
    }
    if ($base[-1] !== '/') {
        $base .= '/';
    }
    $path = ltrim($path, '/');
    return $base . $path;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}


