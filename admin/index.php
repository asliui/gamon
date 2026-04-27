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
  
  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
    <div class="kpi">
        <div class="label">Total Reports Created</div>
        <div class="value" id="count_total">...</div>
    </div>
    <div class="kpi" style="border-color: var(--danger);">
        <div class="label">Pending (Open)</div>
        <div class="value" id="count_open" style="color: var(--danger);">...</div>
    </div>
    <div class="kpi" style="border-color: var(--ok);">
        <div class="label">Successfully Resolved</div>
        <div class="value" id="count_resolved" style="color: var(--ok);">...</div>
    </div>
  </div>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  (async () => {
    try {
      const res = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const data = await res.json();
      
      if (data.ok) {
          document.getElementById('count_total').textContent = data.total;
          document.getElementById('count_open').textContent = data.open;
          document.getElementById('count_resolved').textContent = data.resolved;
      }
    } catch (e) {
      console.error('Failed to load admin summary stats', e);
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>