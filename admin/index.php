<?php

declare(strict_types=1);

// admin/index.php
// Main admin entry point with professional KPI summary cards.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <span class="badge">System Administrator</span>
  <h1>Admin Dashboard</h1>
  <p>Overview of system performance and management tools.</p>

  <div class="row" style="margin-top: 12px; gap: 10px;">
    <a class="btn" href="<?= e(base_url('admin/users.php')) ?>">Manage Users</a>
    <a class="btn" href="<?= e(base_url('admin/categories.php')) ?>">Categories</a>
    <a class="btn" href="<?= e(base_url('admin/areas.php')) ?>">Areas</a>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">All Reports</a>
    <a class="btn" href="<?= e(base_url('admin/analytics.php')) ?>">Full Analytics</a>
  </div>

  <div class="spacer" style="height: 30px;"></div>

  <h2 style="font-size: 1.2rem; margin-bottom: 15px; color: var(--muted);">System Summary</h2>

  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 15px;">
    <div class="kpi">
      <div class="label">Total Reports</div>
      <div class="value" id="kpiTotalReports">—</div>
    </div>
    <div class="kpi" style="border-color: var(--danger);">
      <div class="label">Pending (Open)</div>
      <div class="value" id="kpiPendingReports" style="color: var(--danger);">—</div>
    </div>
    <div class="kpi" style="border-color: var(--ok);">
      <div class="label">Resolved</div>
      <div class="value" id="kpiCleanedReports" style="color: var(--ok);">—</div>
    </div>
    <div class="kpi">
      <div class="label">Total Users</div>
      <div class="value" id="kpiTotalUsers">—</div>
    </div>
  </div>
</div>

<script>
  (async () => {
    try {
      const res = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.ok) {
        throw new Error('Bad response');
      }
      document.getElementById('kpiTotalReports').textContent = String(data.total_reports ?? '0');
      document.getElementById('kpiPendingReports').textContent = String(data.pending_reports ?? '0');
      document.getElementById('kpiCleanedReports').textContent = String(data.cleaned_reports ?? '0');
      document.getElementById('kpiTotalUsers').textContent = String(data.total_users ?? '0');
    } catch (e) {
      document.getElementById('kpiTotalReports').textContent = 'ERR';
      document.getElementById('kpiPendingReports').textContent = 'ERR';
      document.getElementById('kpiCleanedReports').textContent = 'ERR';
      document.getElementById('kpiTotalUsers').textContent = 'ERR';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>