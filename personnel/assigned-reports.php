<?php

declare(strict_types=1);

// personnel/assigned-reports.php
// Lists reports assigned to the current personnel and allows status updates.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'My Assigned Tasks';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>My Assigned Tasks</h1>
  <p>Reports you have assigned to yourself. Update their status as you progress.</p>

  <div class="spacer"></div>

  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border);">
          <th style="padding: 12px 8px;">ID</th>
          <th style="padding: 12px 8px;">Category & Area</th>
          <th style="padding: 12px 8px;">Description</th>
          <th style="padding: 12px 8px;">Status</th>
          <th style="padding: 12px 8px;">Action</th>
        </tr>
      </thead>
      <tbody id="assignedBody">
        <tr><td colspan="5" style="padding: 12px 8px;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  async function updateStatus(reportId, newStatus) {
    try {
      await window.Reports.apiPost('api/reports/update-status.php', {
        report_id: reportId,
        status: newStatus
      });
      // Reload page to see the updated status and buttons
      window.location.reload();
    } catch (err) {
      alert(err.message || 'Failed to update status.');
    }
  }

  (async () => {
    const tbody = document.getElementById('assignedBody');
    try {
      // Fetch only tasks assigned to "me"
      const res = await fetch(window.BASE_URL + 'api/reports/list.php?assigned_to=me&limit=100', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" style="padding: 12px 8px; color: var(--muted);">You have no assigned tasks. Go to Open Reports to take a job.</td></tr>';
          return;
      }

      tbody.innerHTML = '';

      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        let statusColor = 'var(--text)';
        if(item.status === 'assigned') statusColor = 'var(--accent)';
        else if(item.status === 'in_progress') statusColor = '#f59e0b'; // warning color
        else if(item.status === 'resolved') statusColor = 'var(--ok)';

        // Dynamic action buttons based on status
        let actionHtml = '';
        if (item.status === 'assigned') {
          actionHtml = `<button class="btn" style="padding: 6px 12px; font-size: 12px;" onclick="updateStatus(${item.id}, 'in_progress')">Start Work</button>`;
        } else if (item.status === 'in_progress') {
          actionHtml = `<button class="btn" style="padding: 6px 12px; font-size: 12px; background: rgba(54,211,153,0.15); color: var(--ok); border-color: var(--ok);" onclick="updateStatus(${item.id}, 'resolved')">Mark Resolved</button>`;
        } else if (item.status === 'resolved') {
          actionHtml = `<span style="color: var(--muted); font-size: 12px;">Completed</span>`;
        }

        // View button that always appears
        let viewBtn = `<a href="${window.BASE_URL}personnel/report-detail.php?id=${item.id}" class="btn" style="padding: 6px 10px; font-size: 12px; margin-right: 8px;">View</a>`;

        let shortDesc = item.description.length > 50 ? item.description.substring(0, 50) + '...' : item.description;

        tr.innerHTML = `
          <td style="padding: 12px 8px;">#${item.id}</td>
          <td style="padding: 12px 8px;">${item.category}<br><small style="color: var(--muted);">${item.area}</small></td>
          <td style="padding: 12px 8px;">${shortDesc}</td>
          <td style="padding: 12px 8px; color: ${statusColor}; font-weight: bold;">${item.status.toUpperCase()}</td>
          <td style="padding: 12px 8px; display: flex; align-items: center;">${viewBtn} ${actionHtml}</td>
        `;

        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="5" style="padding: 12px 8px; color: var(--danger);">Failed to load data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>