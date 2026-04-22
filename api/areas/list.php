<?php

declare(strict_types=1);

// api/areas/list.php
// Public list of areas/districts.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\Response;

$stmt = DB::pdo()->query('SELECT id, name FROM areas ORDER BY name ASC');
Response::json(['ok' => true, 'items' => $stmt->fetchAll()]);

