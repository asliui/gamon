<?php

declare(strict_types=1);

// admin/analytics.php
// Professional analytics dashboard for admins.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>System Analytics</h1>
  <p>Live summary and data distribution across the system.</p>

  <div class="row">
    <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Export CSV</a>
    <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Export JSON</a>
    <a class="btn" href="<?= e(base_url('api/exports/html.php')) ?>">Export HTML</a>
  </div>

  <div class="spacer"></div>

  <div class="grid cols-2" style="grid-template-columns: repeat(3, 1fr); gap: 15px;">
    <div class="kpi">
        <div class="label">Total Reports</div>
        <div class="value" id="kpi_total">...</div>
    </div>
    <div class="kpi" style="border-color: var(--danger);">
        <div class="label">Open Issues</div>
        <div class="value" id="kpi_open">...</div>
    </div>
    <div class="kpi" style="border-color: var(--ok);">
        <div class="label">Resolved</div>
        <div class="value" id="kpi_resolved">...</div>
    </div>
  </div>

  <div class="spacer"></div>

  <div class="grid cols-2">
      <div class="panel">
          <h2>Distribution by Area</h2>
          <div id="area_stats" style="margin-top: 10px;">Loading...</div>
      </div>
      <div class="panel">
          <h2>Distribution by Category</h2>
          <div id="cat_stats" style="margin-top: 10px;">Loading...</div>
      </div>
  </div>
</div>

<script>
  (async () => {
    try {
      // 1. Load Summary
      const sumRes = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const summary = await sumRes.json();
      if (summary.ok) {
          document.getElementById('kpi_total').textContent = summary.total;
          document.getElementById('kpi_open').textContent = summary.open;
          document.getElementById('kpi_resolved').textContent = summary.resolved;
      }

      // 2. Load Area Stats
      const areaRes = await fetch(window.BASE_URL + 'api/analytics/by-area.php', { credentials: 'same-origin' });
      const areaData = await areaRes.json();
      const areaContainer = document.getElementById('area_stats');
      areaContainer.innerHTML = '';
      areaData.items.forEach(item => {
          areaContainer.innerHTML += `
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                <span>${item.area}</span>
                <span class="badge" style="background:var(--accent); color:white;">${item.count}</span>
            </div>`;
      });

      // 3. Load Category Stats
      const catRes = await fetch(window.BASE_URL + 'api/analytics/by-category.php', { credentials: 'same-origin' });
      const catData = await catRes.json();
      const catContainer = document.getElementById('cat_stats');
      catContainer.innerHTML = '';
      catData.items.forEach(item => {
          catContainer.innerHTML += `
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                <span>${item.category}</span>
                <span class="badge">${item.count}</span>
            </div>`;
      });

    } catch (err) {
      console.error('Analytics load error:', err);
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>