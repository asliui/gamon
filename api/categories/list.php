<?php

declare(strict_types=1);

// api/categories/list.php
// Public list of waste categories.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\Response;

$stmt = DB::pdo()->query('SELECT id, name FROM categories ORDER BY name ASC');
Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);

