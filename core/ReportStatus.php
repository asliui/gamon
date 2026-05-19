<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Controlled report status transitions.
 */
final class ReportStatus
{
    /** @var array<string, list<string>> */
    private const PERSONNEL_TRANSITIONS = [
        'assigned' => ['in_progress', 'resolved'],
        'in_progress' => ['resolved'],
    ];

    /** @var array<string, list<string>> */
    private const ADMIN_TRANSITIONS = [
        'open' => ['assigned', 'in_progress', 'resolved', 'rejected'],
        'assigned' => ['open', 'in_progress', 'resolved', 'rejected'],
        'in_progress' => ['assigned', 'open', 'resolved', 'rejected'],
        'resolved' => ['open', 'assigned', 'in_progress', 'rejected'],
        'rejected' => ['open', 'assigned', 'in_progress', 'resolved'],
    ];

    public static function assertCanTransition(string $current, string $next, bool $isAdmin): void
    {
        if ($current === $next) {
            return;
        }

        $allowed = $isAdmin
            ? (self::ADMIN_TRANSITIONS[$current] ?? [])
            : (self::PERSONNEL_TRANSITIONS[$current] ?? []);

        if (!in_array($next, $allowed, true)) {
            Response::json([
                'ok' => false,
                'error' => "Invalid status transition: {$current} -> {$next}",
            ], 409);
        }
    }

    public static function statusAfterAssignment(string $current): string
    {
        return $current === 'open' ? 'assigned' : $current;
    }
}
