<?php

declare(strict_types=1);

// personnel/open-reports.php
// Lists open reports and allows personnel to assign tasks to themselves.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'Open Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Open Reports</h1>
  <p>List of currently unassigned or open garbage reports.</p>

  <div class="spacer"></div>

  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border);">
          <th style="padding: 12px 8px;">ID</th>
          <th style="padding: 12px 8px;">Category</th>
          <th style="padding: 12px 8px;">Area</th>
          <th style="padding: 12px 8px;">Reported By</th>
          <th style="padding: 12px 8px;">Date</th>
          <th style="padding: 12px 8px;">Action</th>
        </tr>
      </thead>
      <tbody id="openReportsBody">
        <tr><td colspan="6" style="padding: 12px 8px;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>

<script>
  // Function to handle the assignment action
  async function assignToMe(reportId) {
    if (!confirm('Are you sure you want to take this cleanup task?')) {
        return;
    }
    
    try {
      await window.Reports.apiPost('api/reports/assign.php', { report_id: reportId });
      alert('Task successfully assigned to you!');
      // Reload the page to refresh the table and remove the assigned task from the "Open" list
      window.location.reload();
    } catch (err) {
      alert(err.message || 'Failed to assign task.');
    }
  }

  // Fetch and display the open reports
  (async () => {
    const tbody = document.getElementById('openReportsBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/reports/list.php?status=open&limit=100', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--ok);">Great job! There are no open reports at the moment.</td></tr>';
          return;
      }

      tbody.innerHTML = '';

      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        tr.innerHTML = `
          <td style="padding: 12px 8px;">#${item.id}</td>
          <td style="padding: 12px 8px;">${item.category}</td>
          <td style="padding: 12px 8px;">${item.area}</td>
          <td style="padding: 12px 8px;">${item.citizen_email}</td>
          <td style="padding: 12px 8px; color: var(--muted);">${item.created_at}</td>
          <td style="padding: 12px 8px;">
            <a href="${window.BASE_URL}personnel/report-detail.php?id=${item.id}" class="btn" style="padding: 6px 10px; font-size: 12px; margin-right: 5px;">View</a>
            <button class="btn" style="padding: 6px 10px; font-size: 12px;" onclick="assignToMe(${item.id})">Assign</button>
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