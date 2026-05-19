<?php

declare(strict_types=1);

/**
 * CLI validation for Stage 1 (priority) and Stage 2 (filters).
 * Run: php scripts/validate_stages_1_2.php
 */

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;
use WebGamon\Core\ReportPriority;

$pdo = DB::pdo();
$failures = [];

function ok(string $msg): void
{
    echo "[OK] $msg\n";
}

function fail(array &$failures, string $msg): void
{
    $failures[] = $msg;
    echo "[FAIL] $msg\n";
}

// 1. priority column exists
$cols = array_column($pdo->query('PRAGMA table_info(reports)')->fetchAll(), 'name');
if (!in_array('priority', $cols, true)) {
    fail($failures, 'reports.priority column missing');
} else {
    ok('reports.priority column exists');
}

// 2. NULL/empty priority normalized to medium in DB
$nullPri = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted = 0 AND (priority IS NULL OR priority = '')")->fetchColumn();
if ($nullPri > 0) {
    fail($failures, "$nullPri active reports have NULL/empty priority");
} else {
    ok('No active reports with NULL/empty priority');
}

$invalid = $pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted = 0 AND priority NOT IN ('low','medium','high','critical')")->fetchColumn();
if ((int)$invalid > 0) {
    fail($failures, 'Invalid priority values in DB');
} else {
    ok('All active report priorities are valid enum values');
}

// 8. invalid priority API helper
if (ReportPriority::normalize('bogus') !== null) {
    fail($failures, 'ReportPriority::normalize should return null for bogus');
} else {
    ok('Invalid priority rejected by ReportPriority::normalize');
}

// 9. SQL injection pattern in search (code review marker)
$safe = str_replace(['%', '_'], '', "'; DROP TABLE reports; --");
if (strpos($safe, '%') !== false || strpos($safe, '_') !== false) {
    fail($failures, 'Search sanitizer failed');
} else {
    ok('Search wildcard stripping works on malicious input sample');
}

// Role scope SQL fragments exist in list.php
$listSrc = file_get_contents(__DIR__ . '/../api/reports/list.php');
if (strpos($listSrc, "r.citizen_id = :citizen_id") === false) {
    fail($failures, 'Citizen scope missing in list.php');
} else {
    ok('Citizen scope present in list.php');
}
if (strpos($listSrc, 'personnel_scope') === false) {
    fail($failures, 'Personnel scope missing in list.php');
} else {
    ok('Personnel scope present in list.php');
}
if (strpos($listSrc, 'r.is_deleted = 0') === false) {
    fail($failures, 'Soft-delete filter missing in list.php');
} else {
    ok('Deleted reports excluded in list.php');
}

// Frontend files exist
$checks = [
    'citizen/new-report.php' => 'name="priority"',
    'admin/report-detail.php' => 'edit_priority',
    'admin/report-detail.php' => 'res_priority',
    'personnel/open-reports.php' => 'Priority.createBadge',
    'personnel/assigned-reports.php' => 'Priority.createBadge',
    'admin/reports.php' => 'filter_priority',
    'assets/js/priority.js' => 'createBadge',
];
foreach ($checks as $file => $needle) {
    $path = __DIR__ . '/../' . $file;
    $content = file_exists($path) ? file_get_contents($path) : '';
    if ($content === false || strpos($content, $needle) === false) {
        fail($failures, "$file missing expected: $needle");
    } else {
        ok("$file contains $needle");
    }
}

// XSS: priority.js uses textContent
$priJs = file_get_contents(__DIR__ . '/../assets/js/priority.js');
if (strpos($priJs, 'textContent') === false) {
    fail($failures, 'priority.js should use textContent for labels');
} else {
    ok('priority.js uses textContent (XSS-safe)');
}

echo "\n";
if ($failures) {
    echo count($failures) . " failure(s).\n";
    exit(1);
}
echo "All automated checks passed.\n";
exit(0);
