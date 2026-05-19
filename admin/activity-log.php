<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Activity Log';
$logJsFile = dirname(__DIR__) . '/assets/js/activity-log.js';
$logJsVersion = file_exists($logJsFile) ? (string)filemtime($logJsFile) : '1';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Activity Log</h1>
  <p>Audit trail of critical system operations. Newest entries appear first.</p>

  <div class="row" style="margin-top: 12px; gap: 10px; flex-wrap: wrap;">
    <a class="btn" href="<?= e(base_url('admin/index.php')) ?>">Dashboard</a>
    <a class="btn" href="<?= e(base_url('admin/analytics.php')) ?>">Analytics</a>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">Reports</a>
  </div>

  <div class="filter-bar">
    <div class="filter-bar-grid">
      <div class="field" style="margin:0;">
        <label for="log_filter_action">Action</label>
        <select id="log_filter_action">
          <option value="">All actions</option>
        </select>
      </div>
      <div class="field" style="margin:0;">
        <label for="log_filter_entity_type">Entity type</label>
        <select id="log_filter_entity_type">
          <option value="">All types</option>
        </select>
      </div>
      <div class="field" style="margin:0; grid-column: 1 / -1;">
        <label for="log_filter_q">Search</label>
        <input type="search" id="log_filter_q" placeholder="Details, user name or email…" autocomplete="off" />
      </div>
    </div>
    <div class="filter-bar-actions" style="margin-top: 12px;">
      <button type="button" class="btn btn-primary" id="logApplyFiltersBtn">Apply filters</button>
      <button type="button" class="btn" id="logResetFiltersBtn">Reset filters</button>
    </div>
  </div>

  <div class="list-pagination" id="logPaginationBar" style="display: none;">
    <div class="list-pagination-info">
      <span id="logPaginationSummary">Total entries: 0</span>
      <span id="logPaginationPageLabel" class="muted-text">Page 1 of 1</span>
    </div>
    <div class="list-pagination-controls">
      <label class="per-page-label" for="logPerPageSelect">Per page</label>
      <select id="logPerPageSelect" aria-label="Log entries per page">
        <option value="20" selected>20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <button type="button" class="btn" id="logPrevPageBtn" disabled>Previous</button>
      <button type="button" class="btn" id="logNextPageBtn" disabled>Next</button>
    </div>
  </div>

  <div class="activity-log-table-wrap">
    <table class="data-table activity-log-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>User</th>
          <th>Action</th>
          <th>Entity</th>
          <th>ID</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody id="activityLogBody">
        <tr><td colspan="6">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="<?= e(base_url('assets/js/activity-log.js')) ?>?v=<?= e($logJsVersion) ?>"></script>
<script>
  window.ActivityLogPage.init();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
