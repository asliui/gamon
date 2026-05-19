<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\ActivityLog;
use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\DB;
use WebGamon\Core\Response;
use WebGamon\Core\Validator;

$admin = Auth::requireRole('admin');
Csrf::verify();
$data = Response::readJsonBody();

$errors = [];
$errors['id'] = Validator::int($data, 'id');
$errors = array_filter($errors, fn($v) => $v !== null);
if ($errors) {
    Response::json(['ok' => false, 'error' => 'Validation failed', 'fields' => $errors], 422);
}

$id = (int)$data['id'];

$nameStmt = DB::pdo()->prepare('SELECT name FROM areas WHERE id = :id');
$nameStmt->execute([':id' => $id]);
$areaRow = $nameStmt->fetch();

$used = DB::pdo()->prepare('SELECT 1 FROM reports WHERE area_id = :id AND is_deleted = 0 LIMIT 1');
$used->execute([':id' => $id]);
if ($used->fetchColumn()) {
    Response::json(['ok' => false, 'error' => 'Area is used by active reports and cannot be deleted.'], 409);
}

$stmt = DB::pdo()->prepare('DELETE FROM areas WHERE id = :id');
$stmt->execute([':id' => $id]);
if ($stmt->rowCount() === 0) {
    Response::json(['ok' => false, 'error' => 'Area not found'], 404);
}

ActivityLog::write((int)$admin['id'], 'area_deleted', 'area', $id, [
    'name' => $areaRow ? (string)$areaRow['name'] : null,
]);

Response::json(['ok' => true]);
