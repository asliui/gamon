<?php

declare(strict_types=1);

// api/users/list.php — Active users only (admin).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$includeDeleted = isset($_GET['include_deleted']) && $_GET['include_deleted'] === '1';
$roleFilter = isset($_GET['role']) ? trim((string)$_GET['role']) : '';
$allowedRoles = ['admin', 'citizen', 'personnel'];

$conditions = [];
$params = [];

if (!$includeDeleted) {
    $conditions[] = 'is_deleted = 0';
}
if ($roleFilter !== '' && in_array($roleFilter, $allowedRoles, true)) {
    $conditions[] = 'role = :role';
    $params[':role'] = $roleFilter;
}

$sql = '
    SELECT id, name, email, role, created_at, is_deleted, deleted_at
    FROM users
';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY name ASC, id ASC';

$stmt = DB::pdo()->prepare($sql);
$stmt->execute($params);
Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);
