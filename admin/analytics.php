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

  <div class="row" style="margin-top: 12px; gap: 10px; flex-wrap: wrap;">
    <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Export CSV</a>
    <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Export JSON</a>
    <a class="btn" href="<?= e(base_url('api/exports/html.php')) ?>">Export HTML</a>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">All Reports</a>
    <a class="btn" href="<?= e(base_url('admin/import.php')) ?>">Import Data</a>
  </div>

  <div class="spacer"></div>

  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
    <div class="kpi">
      <div class="label">Total Reports</div>
      <div class="value" id="kpi_total_reports">—</div>
    </div>
    <div class="kpi" style="border-color: var(--danger);">
      <div class="label">Pending (Open)</div>
      <div class="value" id="kpi_pending_reports" style="color: var(--danger);">—</div>
    </div>
    <div class="kpi" style="border-color: var(--ok);">
      <div class="label">Resolved</div>
      <div class="value" id="kpi_cleaned_reports" style="color: var(--ok);">—</div>
    </div>
    <div class="kpi">
      <div class="label">Total Users</div>
      <div class="value" id="kpi_total_users">—</div>
    </div>
  </div>

  <div class="spacer"></div>

  <h2 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--muted);">Status Breakdown</h2>
  <div id="status_breakdown" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
    <div class="kpi"><div class="label">Loading…</div></div>
  </div>

  <div class="spacer"></div>

  <h2 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--muted);">Cleanest &amp; Dirtiest Areas</h2>
  <div class="grid cols-2" style="margin-bottom: 20px;">
    <div class="panel">
      <h3 style="margin-top: 0;">Most open reports</h3>
      <div id="dirtiest_areas">Loading...</div>
    </div>
    <div class="panel">
      <h3 style="margin-top: 0;">Fewest open reports</h3>
      <div id="cleanest_areas">Loading...</div>
    </div>
  </div>

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
  const STATUS_LABELS = {
    open: 'Open',
    assigned: 'Assigned',
    in_progress: 'In progress',
    resolved: 'Resolved',
    rejected: 'Rejected',
  };

  function renderStatList(container, items, labelKey) {
    container.textContent = '';
    if (!items || items.length === 0) {
      container.textContent = 'No data.';
      container.style.color = 'var(--muted)';
      return;
    }
    items.forEach((item) => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.justifyContent = 'space-between';
      row.style.padding = '8px 0';
      row.style.borderBottom = '1px solid var(--border)';

      const label = document.createElement('span');
      label.textContent = item[labelKey] ?? '';

      const badge = document.createElement('span');
      badge.className = 'badge';
      badge.textContent = String(item.count ?? 0);

      row.appendChild(label);
      row.appendChild(badge);
      container.appendChild(row);
    });
  }

  function renderStatusBreakdown(breakdown) {
    const container = document.getElementById('status_breakdown');
    container.textContent = '';
    if (!breakdown) {
      container.textContent = 'No breakdown data.';
      return;
    }
    Object.keys(STATUS_LABELS).forEach((key) => {
      const card = document.createElement('div');
      card.className = 'kpi';
      const label = document.createElement('div');
      label.className = 'label';
      label.textContent = STATUS_LABELS[key];
      const value = document.createElement('div');
      value.className = 'value';
      value.textContent = String(breakdown[key] ?? 0);
      card.appendChild(label);
      card.appendChild(value);
      container.appendChild(card);
    });
  }

  (async () => {
    try {
      const sumRes = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const summary = await sumRes.json();
      if (!summary.ok) throw new Error('Summary failed');

      document.getElementById('kpi_total_reports').textContent = String(summary.total_reports ?? 0);
      document.getElementById('kpi_pending_reports').textContent = String(summary.pending_reports ?? 0);
      document.getElementById('kpi_cleaned_reports').textContent = String(summary.cleaned_reports ?? 0);
      document.getElementById('kpi_total_users').textContent = String(summary.total_users ?? 0);
      renderStatusBreakdown(summary.status_breakdown);

      const areaRes = await fetch(window.BASE_URL + 'api/analytics/by-area.php', { credentials: 'same-origin' });
      const areaData = await areaRes.json();
      renderStatList(document.getElementById('area_stats'), areaData.items || [], 'area');

      const catRes = await fetch(window.BASE_URL + 'api/analytics/by-category.php', { credentials: 'same-origin' });
      const catData = await catRes.json();
      renderStatList(document.getElementById('cat_stats'), catData.items || [], 'category');

      const cdRes = await fetch(window.BASE_URL + 'api/analytics/cleanest-dirtiest.php', { credentials: 'same-origin' });
      const cdData = await cdRes.json();
      if (cdData.ok) {
        const mapOpen = (rows) => (rows || []).map((r) => ({ area: r.area, count: r.open_count ?? 0 }));
        renderStatList(document.getElementById('dirtiest_areas'), mapOpen(cdData.dirtiest), 'area');
        renderStatList(document.getElementById('cleanest_areas'), mapOpen(cdData.cleanest), 'area');
      }
    } catch (err) {
      console.error('Analytics load error:', err);
      document.getElementById('area_stats').textContent = 'Failed to load analytics.';
      document.getElementById('cat_stats').textContent = 'Failed to load analytics.';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
