<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
if ($user['role'] !== 'admin') {
    redirect(base_url('dashboard.php'));
}
$title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <span class="badge">Admin</span>
  <h1>Admin Dashboard</h1>
  <p>Manage users, categories, areas, reports, and view analytics (API-first).</p>

  <div class="row" style="margin-top: 12px;">
    <a class="btn" href="<?= e(base_url('admin/users.php')) ?>">Users</a>
    <a class="btn" href="<?= e(base_url('admin/categories.php')) ?>">Categories</a>
    <a class="btn" href="<?= e(base_url('admin/areas.php')) ?>">Areas</a>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">Reports</a>
    <a class="btn" href="<?= e(base_url('admin/analytics.php')) ?>">Analytics</a>
  </div>

  <div class="spacer"></div>
  <h2>Summary (API)</h2>
  <pre id="summary" class="panel" style="white-space: pre-wrap; overflow:auto;"></pre>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  (async () => {
    const pre = document.getElementById('summary');
    try {
      const res = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const data = await res.json();
      pre.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
      pre.textContent = 'Failed to load summary.';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

