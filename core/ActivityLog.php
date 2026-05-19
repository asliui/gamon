<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Central activity / audit log writer. Failures never break the main operation.
 */
final class ActivityLog
{
    public static function write(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $details = null
    ): void {
        try {
            $detailsJson = self::encodeDetails($details);

            $stmt = DB::pdo()->prepare('
                INSERT INTO activity_logs (actor_user_id, action, entity_type, entity_id, details)
                VALUES (:actor_user_id, :action, :entity_type, :entity_id, :details)
            ');
            $stmt->execute([
                ':actor_user_id' => $actorUserId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':details' => $detailsJson,
            ]);
        } catch (\Throwable $e) {
            error_log('ActivityLog write failed: ' . $e->getMessage());
        }
    }

    private static function encodeDetails(?array $details): ?string
    {
        if ($details === null) {
            return null;
        }

        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            return $encoded;
        }

        $fallback = json_encode([
            '_error' => 'json_encode_failed',
            'keys' => array_keys($details),
        ]);

        return $fallback !== false ? $fallback : '{"_error":"json_encode_failed"}';
    }
}
