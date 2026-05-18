<?php

declare(strict_types=1);

namespace WebGamon\Core;

use PDO;

/**
 * Validates and imports categories, areas, or reports from structured data.
 */
final class ImportService
{
    private const MAX_ROWS = 500;
    private const REPORT_STATUSES = ['open', 'assigned', 'in_progress', 'resolved', 'rejected'];

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, skipped: int, errors: list<string>}
     */
    public static function import(string $entity, array $rows): array
    {
        $entity = strtolower(trim($entity));
        if (!in_array($entity, ['categories', 'areas', 'reports'], true)) {
            Response::json(['ok' => false, 'error' => 'Invalid entity. Use categories, areas, or reports.'], 400);
        }

        if (count($rows) > self::MAX_ROWS) {
            Response::json(['ok' => false, 'error' => 'Too many rows (max ' . self::MAX_ROWS . ')'], 400);
        }

        $pdo = DB::pdo();
        $pdo->beginTransaction();

        try {
            $result = match ($entity) {
                'categories' => self::importCategories($pdo, $rows),
                'areas' => self::importAreas($pdo, $rows),
                'reports' => self::importReports($pdo, $rows),
            };
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, skipped: int, errors: list<string>}
     */
    private static function importCategories(PDO $pdo, array $rows): array
    {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO categories (name) VALUES (:name)');
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 1;
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 50) {
                $errors[] = "Row {$line}: invalid category name";
                $skipped++;
                continue;
            }
            $stmt->execute([':name' => $name]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return compact('inserted', 'skipped', 'errors');
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, skipped: int, errors: list<string>}
     */
    private static function importAreas(PDO $pdo, array $rows): array
    {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO areas (name) VALUES (:name)');
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 1;
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 50) {
                $errors[] = "Row {$line}: invalid area name";
                $skipped++;
                continue;
            }
            $stmt->execute([':name' => $name]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return compact('inserted', 'skipped', 'errors');
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, skipped: int, errors: list<string>}
     */
    private static function importReports(PDO $pdo, array $rows): array
    {
        $userCheck = $pdo->prepare('SELECT 1 FROM users WHERE id = :id AND is_deleted = 0 LIMIT 1');
        $catCheck = $pdo->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
        $areaCheck = $pdo->prepare('SELECT 1 FROM areas WHERE id = :id LIMIT 1');
        $insert = $pdo->prepare('
            INSERT INTO reports (citizen_id, category_id, area_id, description, status, created_at, updated_at)
            VALUES (:citizen_id, :category_id, :area_id, :description, :status, :created_at, :updated_at)
        ');

        $inserted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 1;

            $citizenId = (int)($row['citizen_id'] ?? 0);
            $categoryId = (int)($row['category_id'] ?? 0);
            $areaId = (int)($row['area_id'] ?? 0);
            $description = trim((string)($row['description'] ?? 'Imported report'));
            $status = strtolower(trim((string)($row['status'] ?? 'open')));

            if ($citizenId < 1 || $categoryId < 1 || $areaId < 1) {
                $errors[] = "Row {$line}: citizen_id, category_id, and area_id are required";
                $skipped++;
                continue;
            }

            if (mb_strlen($description) < 5 || mb_strlen($description) > 2000) {
                $errors[] = "Row {$line}: description must be 5–2000 characters";
                $skipped++;
                continue;
            }

            if (!in_array($status, self::REPORT_STATUSES, true)) {
                $errors[] = "Row {$line}: invalid status";
                $skipped++;
                continue;
            }

            $userCheck->execute([':id' => $citizenId]);
            if (!$userCheck->fetchColumn()) {
                $errors[] = "Row {$line}: unknown citizen_id";
                $skipped++;
                continue;
            }

            $catCheck->execute([':id' => $categoryId]);
            if (!$catCheck->fetchColumn()) {
                $errors[] = "Row {$line}: unknown category_id";
                $skipped++;
                continue;
            }

            $areaCheck->execute([':id' => $areaId]);
            if (!$areaCheck->fetchColumn()) {
                $errors[] = "Row {$line}: unknown area_id";
                $skipped++;
                continue;
            }

            $createdAt = trim((string)($row['created_at'] ?? ''));
            if ($createdAt === '' || strtotime($createdAt) === false) {
                $createdAt = gmdate('Y-m-d H:i:s');
            } else {
                $createdAt = gmdate('Y-m-d H:i:s', strtotime($createdAt));
            }

            $insert->execute([
                ':citizen_id' => $citizenId,
                ':category_id' => $categoryId,
                ':area_id' => $areaId,
                ':description' => $description,
                ':status' => $status,
                ':created_at' => $createdAt,
                ':updated_at' => $createdAt,
            ]);
            $inserted++;
        }

        return compact('inserted', 'skipped', 'errors');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function parseJsonPayload(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Response::json(['ok' => false, 'error' => 'Invalid JSON file'], 400);
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return self::normalizeRows($decoded['items']);
        }

        if (array_is_list($decoded)) {
            return self::normalizeRows($decoded);
        }

        Response::json(['ok' => false, 'error' => 'JSON must be an array or { "items": [...] }'], 400);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function parseCsvPayload(string $raw, string $entity): array
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            Response::json(['ok' => false, 'error' => 'Could not read CSV'], 500);
        }
        fwrite($stream, $raw);
        rewind($stream);

        $header = fgetcsv($stream);
        if ($header === false) {
            fclose($stream);
            Response::json(['ok' => false, 'error' => 'CSV is empty'], 400);
        }

        $header = array_map(static fn($h) => strtolower(trim((string)$h)), $header);
        $rows = [];

        while (($data = fgetcsv($stream)) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }
            $assoc = [];
            foreach ($header as $idx => $col) {
                if ($col === '') {
                    continue;
                }
                $assoc[$col] = $data[$idx] ?? '';
            }
            $rows[] = $assoc;
        }

        fclose($stream);
        return self::normalizeRows($rows);
    }

    /**
     * @param array<mixed> $items
     * @return list<array<string, mixed>>
     */
    private static function normalizeRows(array $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $rows[] = $item;
        }
        return $rows;
    }
}
