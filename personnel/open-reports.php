<?php

declare(strict_types=1);

// personnel/open-reports.php
// Lists open reports and allows personnel to assign tasks to themselves.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
if ($user['role'] !== 'personnel') {
    redirect(base_url('dashboard.php'));
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

      tbody.textContent = '';

      data.items.forEach((item) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        function cell(text, style) {
          const td = document.createElement('td');
          td.style.padding = '12px 8px';
          if (style) Object.assign(td.style, style);
          td.textContent = text ?? '';
          return td;
        }

        const tdAct = document.createElement('td');
        tdAct.style.padding = '12px 8px';
        const viewLink = document.createElement('a');
        viewLink.href = window.BASE_URL + 'personnel/report-detail.php?id=' + encodeURIComponent(String(item.id));
        viewLink.className = 'btn';
        viewLink.style.cssText = 'padding: 6px 10px; font-size: 12px; margin-right: 5px;';
        viewLink.textContent = 'View';
        const assignBtn = document.createElement('button');
        assignBtn.className = 'btn';
        assignBtn.style.cssText = 'padding: 6px 10px; font-size: 12px;';
        assignBtn.textContent = 'Assign';
        assignBtn.type = 'button';
        assignBtn.addEventListener('click', () => assignToMe(item.id));
        tdAct.append(viewLink, assignBtn);

        tr.append(
          cell('#' + item.id),
          cell(item.category),
          cell(item.area),
          cell(item.citizen_email),
          cell(item.created_at, { color: 'var(--muted)' }),
          tdAct
        );
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--danger);">Failed to load data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>