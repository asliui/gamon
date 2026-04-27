<?php

declare(strict_types=1);

// admin/reports.php
// Formatted table with correct View links for Admin.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>System Reports</h1>
  <p>Overview of all submitted garbage reports across the system.</p>

  <div class="spacer"></div>

  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border);">
          <th style="padding: 12px 8px;">ID</th>
          <th style="padding: 12px 8px;">Category / Area</th>
          <th style="padding: 12px 8px;">Status</th>
          <th style="padding: 12px 8px;">Action</th>
        </tr>
      </thead>
      <tbody id="reportsBody">
        <tr><td colspan="4" style="padding: 12px 8px;">Loading reports...</td></tr>
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
          tbody.innerHTML = '<tr><td colspan="4" style="padding: 12px 8px; color: var(--muted);">No reports found.</td></tr>';
          return;
      }

      tbody.innerHTML = '';
      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        // Correctly formatting the View link with the item ID
        const viewUrl = `${window.BASE_URL}citizen/report-detail.php?id=${item.id}`;

        tr.innerHTML = `
          <td style="padding: 12px 8px; color: var(--muted);">#${item.id}</td>
          <td style="padding: 12px 8px;"><strong>${item.category}</strong><br><small style="color:var(--muted)">${item.area}</small></td>
          <td style="padding: 12px 8px; font-weight: bold; font-size: 12px;">${item.status.toUpperCase()}</td>
          <td style="padding: 12px 8px;">
            <a href="${viewUrl}" class="btn" style="padding: 6px 10px; font-size: 12px;">View</a>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="4" style="padding: 12px 8px; color: var(--danger);">API connection error.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>