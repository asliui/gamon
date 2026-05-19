<?php

declare(strict_types=1);

/**
 * CLI: Load demo seed data (idempotent).
 * Usage: php scripts/seed_demo.php
 */

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\SLA;

$seedPath = __DIR__ . '/../database/seed.sql';
if (!file_exists($seedPath)) {
    fwrite(STDERR, "seed.sql not found.\n");
    exit(1);
}

$sql = file_get_contents($seedPath);
if ($sql === false) {
    fwrite(STDERR, "Could not read seed.sql.\n");
    exit(1);
}

$pdo = DB::pdo();
$pdo->exec($sql);

// Extra category for demo variety (INSERT OR IGNORE in SQL would need seed.sql update — use PHP)
$pdo->exec("INSERT OR IGNORE INTO categories (id, name) VALUES (5, 'Liquid Waste')");

ensureSlaDemoReports($pdo);

echo "Demo seed applied. Default password for demo accounts: Demo123!\n";
echo "Admin: asliuzar4@gmail.com\n";
echo "SLA showcase reports ensured (re-run safe — no duplicates).\n";

/**
 * Idempotent SLA demo reports keyed by fixed description tags.
 */
function ensureSlaDemoReports(\PDO $pdo): void
{
    $citizenId = (int)$pdo->query("SELECT id FROM users WHERE email = 'citizen1@demo.local' LIMIT 1")->fetchColumn();
    $citizen2Id = (int)$pdo->query("SELECT id FROM users WHERE email = 'citizen2@demo.local' LIMIT 1")->fetchColumn();
    if ($citizenId < 1) {
        return;
    }

    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

    $scenarios = [
        [
            'tag' => '[DEMO-SLA] Critical overdue — oil spill',
            'citizen_id' => $citizenId,
            'category_id' => 5,
            'area_id' => 5,
            'priority' => 'critical',
            'status' => 'assigned',
            'created_offset' => '-10 hours',
            'due_at' => $now->modify('-4 hours')->format('Y-m-d H:i:s'),
            'resolved_at' => null,
        ],
        [
            'tag' => '[DEMO-SLA] High due soon — roadside pile',
            'citizen_id' => $citizen2Id > 0 ? $citizen2Id : $citizenId,
            'category_id' => 2,
            'area_id' => 2,
            'priority' => 'high',
            'status' => 'open',
            'created_offset' => '-2 hours',
            'due_at' => $now->modify('+20 hours')->format('Y-m-d H:i:s'),
            'resolved_at' => null,
        ],
        [
            'tag' => '[DEMO-SLA] Medium on time — park bins',
            'citizen_id' => $citizenId,
            'category_id' => 1,
            'area_id' => 1,
            'priority' => 'medium',
            'status' => 'open',
            'created_offset' => '-1 hours',
            'due_at' => $now->modify('+71 hours')->format('Y-m-d H:i:s'),
            'resolved_at' => null,
        ],
        [
            'tag' => '[DEMO-SLA] Resolved late — hazardous pickup',
            'citizen_id' => $citizen2Id > 0 ? $citizen2Id : $citizenId,
            'category_id' => 4,
            'area_id' => 3,
            'priority' => 'high',
            'status' => 'resolved',
            'created_offset' => '-5 days',
            'due_at' => $now->modify('-4 days')->format('Y-m-d H:i:s'),
            'resolved_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
        ],
        [
            'tag' => '[DEMO-SLA] Resolved on time — recyclables',
            'citizen_id' => $citizenId,
            'category_id' => 3,
            'area_id' => 4,
            'priority' => 'low',
            'status' => 'resolved',
            'created_offset' => '-8 days',
            'due_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
            'resolved_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
        ],
    ];

    $existsStmt = $pdo->prepare('SELECT id FROM reports WHERE description = :desc AND is_deleted = 0 LIMIT 1');
    $insertStmt = $pdo->prepare("
        INSERT INTO reports (
            citizen_id, category_id, area_id, description, status, priority,
            due_at, resolved_at, created_at, updated_at, is_deleted
        ) VALUES (
            :citizen_id, :category_id, :area_id, :description, :status, :priority,
            :due_at, :resolved_at, datetime('now', :created_offset), datetime('now'), 0
        )
    ");
    $updateStmt = $pdo->prepare("
        UPDATE reports SET
            status = :status,
            priority = :priority,
            due_at = :due_at,
            resolved_at = :resolved_at,
            updated_at = datetime('now')
        WHERE id = :id
    ");

    foreach ($scenarios as $s) {
        $existsStmt->execute([':desc' => $s['tag']]);
        $existingId = $existsStmt->fetchColumn();

        if ($existingId) {
            $updateStmt->execute([
                ':status' => $s['status'],
                ':priority' => $s['priority'],
                ':due_at' => $s['due_at'],
                ':resolved_at' => $s['resolved_at'],
                ':id' => (int)$existingId,
            ]);
            continue;
        }

        $dueAt = $s['due_at'] ?? SLA::calculateDueAt($s['priority'], $now->format('Y-m-d H:i:s'));
        $insertStmt->execute([
            ':citizen_id' => $s['citizen_id'],
            ':category_id' => $s['category_id'],
            ':area_id' => $s['area_id'],
            ':description' => $s['tag'],
            ':status' => $s['status'],
            ':priority' => $s['priority'],
            ':due_at' => $dueAt,
            ':resolved_at' => $s['resolved_at'],
            ':created_offset' => $s['created_offset'],
        ]);
    }
}
