<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * SLA deadlines by report priority (UTC, aligned with app timezone).
 */
final class SLA
{
    private const CLOSED_STATUSES = ['resolved', 'rejected'];

    /** Hours until due from created_at. */
    private const HOURS_BY_PRIORITY = [
        'low' => 168,      // 7 days
        'medium' => 72,    // 3 days
        'high' => 24,
        'critical' => 6,
    ];

    public static function calculateDueAt(string $priority, string $createdAt): string
    {
        $key = strtolower(trim($priority));
        if (!isset(self::HOURS_BY_PRIORITY[$key])) {
            $key = ReportPriority::DEFAULT;
        }

        $base = self::parseDateTime($createdAt) ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $due = $base->modify('+' . self::HOURS_BY_PRIORITY[$key] . ' hours');

        return $due->format('Y-m-d H:i:s');
    }

    public static function isOverdue(array $report): bool
    {
        $status = (string)($report['status'] ?? '');
        if (in_array($status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        $dueAt = $report['due_at'] ?? null;
        if ($dueAt === null || $dueAt === '') {
            return false;
        }

        $due = self::parseDateTime((string)$dueAt);
        if ($due === null) {
            return false;
        }

        return $due < new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function isResolvedLate(array $report): bool
    {
        if ((string)($report['status'] ?? '') !== 'resolved') {
            return false;
        }

        $resolvedAt = $report['resolved_at'] ?? null;
        $dueAt = $report['due_at'] ?? null;
        if ($resolvedAt === null || $resolvedAt === '' || $dueAt === null || $dueAt === '') {
            return false;
        }

        $resolved = self::parseDateTime((string)$resolvedAt);
        $due = self::parseDateTime((string)$dueAt);
        if ($resolved === null || $due === null) {
            return false;
        }

        return $resolved > $due;
    }

    /**
     * @return array{hours: int, human: string}|null
     */
    public static function getRemainingTime(array $report): ?array
    {
        $dueAt = $report['due_at'] ?? null;
        if ($dueAt === null || $dueAt === '') {
            return null;
        }

        $due = self::parseDateTime((string)$dueAt);
        if ($due === null) {
            return null;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $diffSeconds = $due->getTimestamp() - $now->getTimestamp();
        $hours = (int)round($diffSeconds / 3600);

        if ($hours === 0 && $diffSeconds !== 0) {
            $hours = $diffSeconds > 0 ? 1 : -1;
        }

        return [
            'hours' => $hours,
            'human' => self::formatHumanRemaining($hours, (string)($report['status'] ?? '')),
        ];
    }

    /**
     * Attach due_at, resolved_at, is_overdue, is_resolved_late, remaining_time to a report row.
     *
     * @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    public static function enrichReport(array $report): array
    {
        $report['is_overdue'] = self::isOverdue($report);
        $report['is_resolved_late'] = self::isResolvedLate($report);
        $report['remaining_time'] = self::getRemainingTime($report);

        return $report;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function enrichReports(array $items): array
    {
        foreach ($items as &$item) {
            $item = self::enrichReport($item);
        }
        unset($item);

        return $items;
    }

    public static function shouldRecalculateDueOnPriorityChange(string $status): bool
    {
        return !in_array($status, self::CLOSED_STATUSES, true);
    }

    private static function formatHumanRemaining(int $hours, string $status): string
    {
        if (in_array($status, self::CLOSED_STATUSES, true)) {
            return $hours >= 0 ? 'Closed on time' : 'Closed';
        }

        $abs = abs($hours);
        if ($hours < 0) {
            if ($abs < 24) {
                return $abs === 1 ? '1 hour overdue' : $abs . ' hours overdue';
            }
            $days = (int)round($abs / 24);

            return $days === 1 ? '1 day overdue' : $days . ' days overdue';
        }

        if ($abs < 24) {
            return $abs <= 1 ? 'Due in 1h' : 'Due in ' . $abs . 'h';
        }

        $days = (int)round($abs / 24);

        return $days === 1 ? 'Due in 1d' : 'Due in ' . $days . 'd';
    }

    private static function parseDateTime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
