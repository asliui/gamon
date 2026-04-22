<?php

declare(strict_types=1);

// api/analytics/summary.php
// Minimal summary counts for admin dashboard.

require_once __DIR__ . '/../../core/bootstrap.php';

use WebGamon\Core\Auth;
use WebGamon\Core\DB;
use WebGamon\Core\Response;

Auth::requireRole('admin');

$total = (int)DB::pdo()->query('SELECT COUNT(*) AS c FROM reports')->fetch()['c'];
$open = (int)DB::pdo()->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'open'")->fetch()['c'];
$resolved = (int)DB::pdo()->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'resolved'")->fetch()['c'];

Response::json(['ok' => true, 'total' => $total, 'open' => $open, 'resolved' => $resolved]);

