<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Analytics';
$chartsJsFile = dirname(__DIR__) . '/assets/js/analytics-charts.js';
$chartsJsVersion = file_exists($chartsJsFile) ? (string)filemtime($chartsJsFile) : '1';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>System Analytics</h1>
  <p>Live summary and visual distributions across active reports (soft-deleted reports excluded).</p>

  <div class="row" style="margin-top: 12px; gap: 10px; flex-wrap: wrap;">
    <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Export CSV</a>
    <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Export JSON</a>
    <a class="btn" href="<?= e(base_url('api/exports/html.php')) ?>">Export HTML</a>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">All Reports</a>
    <a class="btn" href="<?= e(base_url('admin/activity-log.php')) ?>">Activity Log</a>
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
    <div class="kpi" style="border-color: #ff8a96;">
      <div class="label">Overdue</div>
      <div class="value" id="kpi_overdue_reports" style="color: #ff8a96;">—</div>
    </div>
    <div class="kpi" style="border-color: #f0abfc;">
      <div class="label">Resolved Late</div>
      <div class="value" id="kpi_resolved_late" style="color: #f0abfc;">—</div>
    </div>
    <div class="kpi" style="border-color: var(--accent);">
      <div class="label">SLA Compliance</div>
      <div class="value" id="kpi_sla_compliance" style="color: var(--accent);">—</div>
    </div>
  </div>

  <div class="spacer"></div>

  <h2 style="font-size: 1.1rem; margin-bottom: 4px;">Report Distributions</h2>
  <p style="color: var(--muted); font-size: 14px; margin: 0 0 8px;">Horizontal bar charts by status, priority, category, and area.</p>
  <div id="analyticsChartsGrid" class="analytics-grid" aria-live="polite">
    <p class="chart-empty">Loading charts…</p>
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
</div>

<script src="<?= e(base_url('assets/js/analytics-charts.js')) ?>?v=<?= e($chartsJsVersion) ?>"></script>
<script>
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

  (async () => {
    window.AnalyticsCharts.load('analyticsChartsGrid');

    try {
      const sumRes = await fetch(window.BASE_URL + 'api/analytics/summary.php', { credentials: 'same-origin' });
      const summary = await sumRes.json();
      if (!summary.ok) throw new Error('Summary failed');

      document.getElementById('kpi_total_reports').textContent = String(summary.total_reports ?? 0);
      document.getElementById('kpi_pending_reports').textContent = String(summary.pending_reports ?? 0);
      document.getElementById('kpi_cleaned_reports').textContent = String(summary.cleaned_reports ?? 0);
      document.getElementById('kpi_total_users').textContent = String(summary.total_users ?? 0);
      document.getElementById('kpi_overdue_reports').textContent = String(summary.overdue_reports ?? 0);
      document.getElementById('kpi_resolved_late').textContent = String(summary.resolved_late_reports ?? 0);
      document.getElementById('kpi_sla_compliance').textContent = String(summary.sla_compliance_pct ?? 0) + '%';

      const cdRes = await fetch(window.BASE_URL + 'api/analytics/cleanest-dirtiest.php', { credentials: 'same-origin' });
      const cdData = await cdRes.json();
      if (cdData.ok) {
        const mapOpen = (rows) => (rows || []).map((r) => ({ area: r.area, count: r.open_count ?? 0 }));
        renderStatList(document.getElementById('dirtiest_areas'), mapOpen(cdData.dirtiest), 'area');
        renderStatList(document.getElementById('cleanest_areas'), mapOpen(cdData.cleanest), 'area');
      }
    } catch (err) {
      console.error('Analytics load error:', err);
      if (window.Toast) window.Toast.error(err.message || 'Failed to load summary.');
      document.getElementById('dirtiest_areas').textContent = 'Failed to load.';
      document.getElementById('cleanest_areas').textContent = 'Failed to load.';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
