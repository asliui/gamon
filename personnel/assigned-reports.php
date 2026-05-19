<?php

declare(strict_types=1);

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
  <p>Reports assigned to you. You can update assignment progress separately from the overall report status.</p>
  <div class="spacer"></div>

  <div style="overflow-x: auto;">
    <table class="data-table" id="assignedTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Category / Area</th>
          <th>Priority</th>
          <th>SLA</th>
          <th>Description</th>
          <th>Report Status</th>
          <th>Work Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="assignedBody">
        <tr><td colspan="8">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  const PROGRESS_LABELS = {
    not_started: 'Not started',
    in_progress: 'In progress',
    completed: 'Completed',
  };

  function notify(msg, ok) {
    if (window.Toast) {
      ok ? window.Toast.success(msg) : window.Toast.error(msg);
    } else {
      alert(msg);
    }
  }

  async function updateReportStatus(reportId, newStatus) {
    try {
      await window.WG.apiPost('api/reports/update-status.php', {
        report_id: reportId,
        status: newStatus,
      });
      notify('Report status updated.', true);
      loadAssigned();
    } catch (err) {
      notify(err.message || 'Could not update status.', false);
    }
  }

  async function updateAssignmentProgress(reportId, progressStatus, progressNote, btn) {
    if (btn) btn.classList.add('btn-loading');
    try {
      await window.WG.apiPost('api/reports/update-assignment-progress.php', {
        report_id: reportId,
        progress_status: progressStatus,
        progress_note: progressNote,
      });
      notify('Work progress updated.', true);
      loadAssigned();
    } catch (err) {
      notify(err.message || 'Could not update progress.', false);
    } finally {
      if (btn) btn.classList.remove('btn-loading');
    }
  }

  function buildProgressForm(item) {
    const wrap = document.createElement('div');
    wrap.className = 'progress-form';
    wrap.style.marginTop = '8px';
    wrap.style.padding = '10px';
    wrap.style.border = '1px solid var(--border)';
    wrap.style.borderRadius = '10px';
    wrap.style.background = 'rgba(0,0,0,0.15)';

    const selLabel = document.createElement('label');
    selLabel.textContent = 'Work status';
    selLabel.style.display = 'block';
    selLabel.style.fontSize = '12px';
    selLabel.style.color = 'var(--muted)';
    selLabel.style.marginBottom = '4px';

    const sel = document.createElement('select');
    sel.style.width = '100%';
    sel.style.marginBottom = '8px';
    ['not_started', 'in_progress', 'completed'].forEach((val) => {
      const opt = document.createElement('option');
      opt.value = val;
      opt.textContent = PROGRESS_LABELS[val];
      if ((item.assignment_progress_status || 'not_started') === val) {
        opt.selected = true;
      }
      sel.appendChild(opt);
    });

    const noteLabel = document.createElement('label');
    noteLabel.textContent = 'Note';
    noteLabel.style.display = 'block';
    noteLabel.style.fontSize = '12px';
    noteLabel.style.color = 'var(--muted)';
    noteLabel.style.marginBottom = '4px';

    const note = document.createElement('textarea');
    note.rows = 2;
    note.style.width = '100%';
    note.style.marginBottom = '8px';
    note.placeholder = 'e.g. Team arrived on site…';
    note.value = item.assignment_progress_note || '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm';
    btn.style.background = 'var(--accent)';
    btn.textContent = 'Update progress';
    btn.addEventListener('click', () => {
      updateAssignmentProgress(item.id, sel.value, note.value.trim(), btn);
    });

    wrap.append(selLabel, sel, noteLabel, note, btn);
    return wrap;
  }

  async function loadAssigned() {
    const tbody = document.getElementById('assignedBody');
    tbody.innerHTML = '<tr><td colspan="8">Loading…</td></tr>';
    try {
      const data = await window.WG.apiGet('api/reports/list.php?assigned_to=me&per_page=50');
      if (!data.items || data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="muted-cell">No assigned tasks. Pick up tasks from open reports.</td></tr>';
        return;
      }

      tbody.textContent = '';
      data.items.forEach((item) => {
        const ps = item.assignment_progress_status || 'not_started';
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        const tdId = document.createElement('td');
        tdId.textContent = '#' + item.id;

        const tdCat = document.createElement('td');
        tdCat.appendChild(document.createTextNode(item.category || ''));
        tdCat.appendChild(document.createElement('br'));
        const small = document.createElement('small');
        small.style.color = 'var(--muted)';
        small.textContent = item.area || '';
        tdCat.appendChild(small);

        const tdPri = document.createElement('td');
        tdPri.appendChild(window.Priority.createBadge(item.priority || 'medium'));

        const tdSla = document.createElement('td');
        if (window.SLA) tdSla.appendChild(window.SLA.createBadge(item));

        const desc = String(item.description || '');
        const tdDesc = document.createElement('td');
        tdDesc.textContent = desc.length > 60 ? desc.substring(0, 60) + '...' : desc;
        tdDesc.title = desc;

        const tdStatus = document.createElement('td');
        tdStatus.style.fontWeight = 'bold';
        tdStatus.textContent = String(item.status || '').toUpperCase();

        const tdProgress = document.createElement('td');
        const progMain = document.createElement('div');
        progMain.style.fontWeight = 'bold';
        progMain.textContent = PROGRESS_LABELS[ps] || ps;
        tdProgress.appendChild(progMain);
        if (item.assignment_progress_updated_at) {
          const upd = document.createElement('small');
          upd.style.display = 'block';
          upd.style.color = 'var(--muted)';
          upd.textContent = 'Updated: ' + item.assignment_progress_updated_at;
          tdProgress.appendChild(upd);
        }
        if (item.assignment_progress_note) {
          const nt = document.createElement('small');
          nt.style.display = 'block';
          nt.style.marginTop = '4px';
          nt.textContent = item.assignment_progress_note;
          tdProgress.appendChild(nt);
        }

        const tdAct = document.createElement('td');
        tdAct.style.verticalAlign = 'top';

        const viewLink = document.createElement('a');
        viewLink.href = window.BASE_URL + 'personnel/report-detail.php?id=' + encodeURIComponent(String(item.id));
        viewLink.className = 'btn btn-sm';
        viewLink.textContent = 'View';
        viewLink.style.marginBottom = '8px';
        viewLink.style.display = 'inline-block';
        tdAct.appendChild(viewLink);

        if (item.status === 'assigned') {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'btn btn-sm';
          b.textContent = 'Start work (report)';
          b.style.marginLeft = '6px';
          b.addEventListener('click', () => updateReportStatus(item.id, 'in_progress'));
          tdAct.appendChild(b);
        } else if (item.status === 'in_progress') {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'btn btn-sm';
          b.textContent = 'Mark resolved (report)';
          b.style.marginLeft = '6px';
          b.addEventListener('click', () => updateReportStatus(item.id, 'resolved'));
          tdAct.appendChild(b);
        }

        tdAct.appendChild(buildProgressForm(item));

        tr.append(tdId, tdCat, tdPri, tdSla, tdDesc, tdStatus, tdProgress, tdAct);
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="8" class="danger-cell">Failed to load data.</td></tr>';
    }
  }

  loadAssigned();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
