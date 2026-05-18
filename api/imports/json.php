<?php

declare(strict_types=1);

// api/imports/json.php — Admin JSON import (categories, areas, reports).

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\Csrf;
use WebGamon\Core\ImportService;
use WebGamon\Core\Response;

Auth::requireRole('admin');
Csrf::verify();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$entity = strtolower(trim((string)($_POST['entity'] ?? '')));
if ($entity === '') {
    Response::json(['ok' => false, 'error' => 'Missing entity (categories, areas, or reports)'], 400);
}

$config = require __DIR__ . '/../../config/config.php';
$maxBytes = (int)($config['import']['max_bytes'] ?? (2 * 1024 * 1024));

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    Response::json(['ok' => false, 'error' => 'JSON file is required'], 400);
}

if ((int)$_FILES['file']['size'] > $maxBytes) {
    Response::json(['ok' => false, 'error' => 'File exceeds maximum import size'], 413);
}

$tmp = (string)$_FILES['file']['tmp_name'];
if (!is_uploaded_file($tmp)) {
    Response::json(['ok' => false, 'error' => 'Invalid upload'], 400);
}

$ext = strtolower(pathinfo((string)$_FILES['file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'json') {
    Response::json(['ok' => false, 'error' => 'Only .json files are allowed'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp);
$allowed = ['application/json', 'text/plain', 'text/json', 'application/octet-stream'];
if ($mime === false || !in_array($mime, $allowed, true)) {
    Response::json(['ok' => false, 'error' => 'Invalid JSON file type'], 400);
}

$raw = file_get_contents($tmp);
if ($raw === false || trim($raw) === '') {
    Response::json(['ok' => false, 'error' => 'Empty file'], 400);
}

$rows = ImportService::parseJsonPayload($raw);
$result = ImportService::import($entity, $rows);

Response::json([
    'ok' => true,
    'entity' => $entity,
    'inserted' => $result['inserted'],
    'skipped' => $result['skipped'],
    'errors' => $result['errors'],
]);
