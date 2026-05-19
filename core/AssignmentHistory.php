<?php

declare(strict_types=1);

namespace WebGamon\Core;

use PDO;

/**
 * Logs personnel assignment changes for audit/history views.
 */
final class AssignmentHistory
{
    public static function record(
        int $reportId,
        ?int $oldPersonnelId,
        int $newPersonnelId,
        int $assignedBy,
        PDO $pdo = null
    ): void {
        $pdo = $pdo ?? DB::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO assignment_history (report_id, old_personnel_id, new_personnel_id, assigned_by)
            VALUES (:report_id, :old_personnel_id, :new_personnel_id, :assigned_by)
        ');
        $stmt->execute([
            ':report_id' => $reportId,
            ':old_personnel_id' => $oldPersonnelId,
            ':new_personnel_id' => $newPersonnelId,
            ':assigned_by' => $assignedBy,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForReport(int $reportId, PDO $pdo = null): array
    {
        $pdo = $pdo ?? DB::pdo();
        $stmt = $pdo->prepare('
            SELECT
                h.id,
                h.report_id,
                h.old_personnel_id,
                h.new_personnel_id,
                h.assigned_by,
                h.assigned_at,
                ou.name AS old_personnel_name,
                nu.name AS new_personnel_name,
                ab.name AS assigned_by_name
            FROM assignment_history h
            LEFT JOIN users ou ON ou.id = h.old_personnel_id
            JOIN users nu ON nu.id = h.new_personnel_id
            JOIN users ab ON ab.id = h.assigned_by
            WHERE h.report_id = :report_id
            ORDER BY h.id DESC
        ');
        $stmt->execute([':report_id' => $reportId]);
        return $stmt->fetchAll();
    }
}
