<?php

declare(strict_types=1);

// citizen/my-reports.php
// Lists citizen reports using /api/reports/list.php.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();

// SECURITY FIX: Redirect if not logged in OR if not a citizen
if (!$user || $user['role'] !== 'citizen') {
    redirect(base_url('dashboard.php'));
}

$title = 'My Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>My Reports</h1>
  <p>The list below is loaded dynamically via <code>/api/reports/list.php</code> using Ajax (fetch).</p>

  <div class="row">
    <a class="btn" href="<?= e(base_url('citizen/new-report.php')) ?>">New report</a>
  </div>

  <div class="spacer"></div>
  
  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border);">
          <th style="padding: 12px 8px;">ID</th>
          <th style="padding: 12px 8px;">Category</th>
          <th style="padding: 12px 8px;">Area</th>
          <th style="padding: 12px 8px;">Status</th>
          <th style="padding: 12px 8px;">Date</th>
          <th style="padding: 12px 8px;">Action</th>
        </tr>
      </thead>
      <tbody id="reportsBody">
        <tr><td colspan="6" style="padding: 12px 8px;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  (async () => {
    const tbody = document.getElementById('reportsBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/reports/list.php?limit=100', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--muted);">You have not created any reports yet.</td></tr>';
          return;
      }

      tbody.innerHTML = '';

      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        let statusColor = 'var(--text)';
        if(item.status === 'open') statusColor = 'var(--danger)';
        else if(item.status === 'resolved') statusColor = 'var(--ok)';
        else if(item.status === 'in_progress' || item.status === 'assigned') statusColor = 'var(--accent)';

        tr.innerHTML = `
          <td style="padding: 12px 8px;">#${item.id}</td>
          <td style="padding: 12px 8px;">${item.category}</td>
          <td style="padding: 12px 8px;">${item.area}</td>
          <td style="padding: 12px 8px; color: ${statusColor}; font-weight: bold;">${item.status.toUpperCase()}</td>
          <td style="padding: 12px 8px; color: var(--muted);">${item.created_at}</td>
          <td style="padding: 12px 8px;">
            <a href="${window.BASE_URL}citizen/report-detail.php?id=${item.id}" class="btn" style="padding: 5px 10px; font-size: 12px;">View</a>
          </td>
        `;

        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--danger);">Failed to load data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>