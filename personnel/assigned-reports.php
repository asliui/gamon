<?php

declare(strict_types=1);

// personnel/assigned-reports.php
// Lists reports assigned to the current personnel and allows status updates.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
if ($user['role'] !== 'personnel') {
    redirect(base_url('dashboard.php'));
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

      tbody.textContent = '';

      data.items.forEach((item) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        let statusColor = 'var(--text)';
        if (item.status === 'assigned') statusColor = 'var(--accent)';
        else if (item.status === 'in_progress') statusColor = '#f59e0b';
        else if (item.status === 'resolved') statusColor = 'var(--ok)';

        const tdId = document.createElement('td');
        tdId.style.padding = '12px 8px';
        tdId.textContent = '#' + item.id;

        const tdCat = document.createElement('td');
        tdCat.style.padding = '12px 8px';
        tdCat.appendChild(document.createTextNode(item.category ?? ''));
        tdCat.appendChild(document.createElement('br'));
        const small = document.createElement('small');
        small.style.color = 'var(--muted)';
        small.textContent = item.area ?? '';
        tdCat.appendChild(small);

        const desc = String(item.description ?? '');
        const tdDesc = document.createElement('td');
        tdDesc.style.padding = '12px 8px';
        tdDesc.textContent = desc.length > 50 ? desc.substring(0, 50) + '...' : desc;

        const tdStatus = document.createElement('td');
        tdStatus.style.padding = '12px 8px';
        tdStatus.style.color = statusColor;
        tdStatus.style.fontWeight = 'bold';
        tdStatus.textContent = String(item.status || '').toUpperCase();

        const tdAct = document.createElement('td');
        tdAct.style.padding = '12px 8px';
        tdAct.style.display = 'flex';
        tdAct.style.alignItems = 'center';

        const viewLink = document.createElement('a');
        viewLink.href = window.BASE_URL + 'personnel/report-detail.php?id=' + encodeURIComponent(String(item.id));
        viewLink.className = 'btn';
        viewLink.style.cssText = 'padding: 6px 10px; font-size: 12px; margin-right: 8px;';
        viewLink.textContent = 'View';
        tdAct.appendChild(viewLink);

        if (item.status === 'assigned') {
          const btn = document.createElement('button');
          btn.className = 'btn';
          btn.style.cssText = 'padding: 6px 12px; font-size: 12px;';
          btn.textContent = 'Start Work';
          btn.type = 'button';
          btn.addEventListener('click', () => updateStatus(item.id, 'in_progress'));
          tdAct.appendChild(btn);
        } else if (item.status === 'in_progress') {
          const btn = document.createElement('button');
          btn.className = 'btn';
          btn.style.cssText = 'padding: 6px 12px; font-size: 12px; background: rgba(54,211,153,0.15); color: var(--ok); border-color: var(--ok);';
          btn.textContent = 'Mark Resolved';
          btn.type = 'button';
          btn.addEventListener('click', () => updateStatus(item.id, 'resolved'));
          tdAct.appendChild(btn);
        } else if (item.status === 'resolved') {
          const span = document.createElement('span');
          span.style.cssText = 'color: var(--muted); font-size: 12px;';
          span.textContent = 'Completed';
          tdAct.appendChild(span);
        }

        tr.append(tdId, tdCat, tdDesc, tdStatus, tdAct);
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="5" style="padding: 12px 8px; color: var(--danger);">Failed to load data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>