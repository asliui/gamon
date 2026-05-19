<?php

declare(strict_types=1);

// admin/report-detail.php
// Admin report detail view (API-first via fetch).

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = 'Admin - Report Details';
$timelineJsFile = dirname(__DIR__) . '/assets/js/report-timeline.js';
$timelineJsVersion = file_exists($timelineJsFile) ? (string)filemtime($timelineJsFile) : '1';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel max-560">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Report Details</h1>
    <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">Back to Reports</a>
  </div>
  <div class="spacer"></div>

  <div id="loading">Loading details...</div>

  <div id="reportContent" style="display: none;">
    <div class="kpi">
      <div class="label">Report ID</div>
      <div class="value" id="res_id"></div>
    </div>
    <div class="spacer"></div>

    <div class="field">
      <label>Status</label>
      <div id="res_status" style="font-weight: bold; font-size: 1.2rem;"></div>
    </div>

    <div class="field">
      <label>Priority</label>
      <div id="res_priority"></div>
    </div>

    <div class="field">
      <label>SLA / Deadline</label>
      <div id="res_sla"></div>
      <small id="res_due_at" style="display:block;color:var(--muted);margin-top:4px;"></small>
    </div>

    <div class="field">
      <label>Category &amp; Area</label>
      <div id="res_cat_area" style="color: var(--text);"></div>
    </div>

    <div class="field">
      <label>Reported by</label>
      <div id="res_citizen" style="color: var(--text);"></div>
    </div>

    <div class="field">
      <label>Assignment</label>
      <div id="res_assignment" style="color: var(--text);"></div>
    </div>

    <div class="field">
      <label>Description</label>
      <p id="res_desc" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; border: 1px solid var(--border); margin-top: 5px;"></p>
    </div>

    <div class="field" id="imageContainer" style="display: none; margin-top: 15px;">
      <label>Attached Photo</label>
      <img id="res_img" src="" alt="Waste Report Photo" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--border); margin-top: 5px;" />
    </div>

    <div class="field">
      <label>Reported Date</label>
      <div id="res_date" style="color: var(--muted); margin-top: 10px;"></div>
    </div>

    <div class="spacer"></div>

    <div class="panel" id="editSection">
      <h2 style="margin-top: 0;">Edit Report</h2>
      <form id="editReportForm">
        <div class="field">
          <label for="edit_description">Description</label>
          <textarea id="edit_description" name="description" rows="4" required></textarea>
        </div>
        <div class="field">
          <label for="edit_category">Category</label>
          <select id="edit_category" name="category_id" required></select>
        </div>
        <div class="field">
          <label for="edit_area">Area</label>
          <select id="edit_area" name="area_id" required></select>
        </div>
        <div class="field">
          <label for="edit_priority">Priority</label>
          <select id="edit_priority" name="priority" required>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div class="field">
          <label for="edit_status">Status</label>
          <select id="edit_status" name="status" required>
            <option value="open">open</option>
            <option value="assigned">assigned</option>
            <option value="in_progress">in_progress</option>
            <option value="resolved">resolved</option>
            <option value="rejected">rejected</option>
          </select>
        </div>
        <div class="actions">
          <button type="submit" class="btn" id="saveReportBtn">Save changes</button>
          <button type="button" class="btn danger" id="deleteReportBtn">Soft delete report</button>
        </div>
      </form>
    </div>

    <div class="panel" id="timelineSection">
      <h2 style="margin-top: 0;">Timeline</h2>
      <p class="hint" style="margin: 0 0 8px; color: var(--muted); font-size: 13px;">Operational history from the activity log.</p>
      <div id="reportTimeline"></div>
    </div>

    <div class="panel" id="historySection">
      <h2 style="margin-top: 0;">Assignment History</h2>
      <table class="data-table">
        <thead>
          <tr><th>When</th><th>From</th><th>To</th><th>By</th></tr>
        </thead>
        <tbody id="historyBody"><tr><td colspan="4">Loading...</td></tr></tbody>
      </table>
    </div>

    <div class="panel" id="assignSection" style="border-color: rgba(110, 231, 255, 0.25);">
      <h2 style="margin-top: 0;">Assign Personnel</h2>
      <p class="hint" id="assignHint">Select active personnel to assign or reassign this report.</p>

      <div id="assignMsg" class="alert" style="display: none; margin-bottom: 12px;"></div>

      <div class="field">
        <label for="personnelSelect">Personnel</label>
        <select id="personnelSelect" name="personnel_id">
          <option value="">Select personnel</option>
        </select>
      </div>

      <div class="actions" style="margin-top: 12px;">
        <button type="button" class="btn" id="assignBtn" style="background: var(--accent); font-weight: bold;">
          Assign personnel
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const reportId = <?= (int)$id ?>;
    const loading = document.getElementById('loading');
    const assignMsg = document.getElementById('assignMsg');
    const assignBtn = document.getElementById('assignBtn');
    const personnelSelect = document.getElementById('personnelSelect');
    const assignHint = document.getElementById('assignHint');
    const assignSection = document.getElementById('assignSection');

    let personnelList = [];
    let categories = [];
    let areas = [];
    let currentReport = null;

    function notify(msg, ok) {
      if (window.Toast) {
        ok ? window.Toast.success(msg) : window.Toast.error(msg);
      }
    }

    function showAssignMsg(text, ok) {
      assignMsg.style.display = 'block';
      assignMsg.className = ok ? 'alert ok' : 'alert';
      assignMsg.textContent = text;
    }

    function hideAssignMsg() {
      assignMsg.style.display = 'none';
    }

    const PROGRESS_LABELS = {
      not_started: 'Not started',
      in_progress: 'In progress',
      completed: 'Completed',
    };

    function setAssignmentDisplay(item) {
      const assignEl = document.getElementById('res_assignment');
      if (item.personnel_id) {
        let text =
          (item.personnel_name || 'Personnel #' + item.personnel_id) +
          ' (' + (item.personnel_email || '—') + ') — assigned ' + (item.assigned_at || '');
        const ps = item.assignment_progress_status || 'not_started';
        text += '\nWork status: ' + (PROGRESS_LABELS[ps] || ps);
        if (item.assignment_progress_updated_at) {
          text += '\nLast updated: ' + item.assignment_progress_updated_at;
        }
        if (item.assignment_progress_note) {
          text += '\nNote: ' + item.assignment_progress_note;
        }
        assignEl.textContent = text;
        assignEl.style.whiteSpace = 'pre-line';
        assignEl.style.color = 'var(--text)';
      } else {
        assignEl.textContent = 'Not assigned yet';
        assignEl.style.color = 'var(--muted)';
      }
    }

    function populatePersonnelSelect(selectedId) {
      personnelSelect.textContent = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'Select personnel';
      personnelSelect.appendChild(placeholder);

      personnelList.forEach((p) => {
        const opt = document.createElement('option');
        opt.value = String(p.id);
        opt.textContent = (p.name || 'User') + ' (' + (p.email || '') + ')';
        if (selectedId && Number(p.id) === Number(selectedId)) {
          opt.selected = true;
        }
        personnelSelect.appendChild(opt);
      });
    }

    function updateAssignUi(item) {
      const closed = item.status === 'resolved' || item.status === 'rejected';
      assignSection.style.display = closed ? 'none' : 'block';
      assignBtn.disabled = closed || personnelList.length === 0;
      personnelSelect.disabled = closed || personnelList.length === 0;

      if (personnelList.length === 0) {
        assignHint.textContent = 'No active personnel accounts found. Create or promote users first.';
        return;
      }

      if (item.personnel_id) {
        assignHint.textContent =
          'Currently assigned to ' +
          (item.personnel_name || 'personnel #' + item.personnel_id) +
          '. Choose another personnel to reassign.';
      } else {
        assignHint.textContent = 'Select active personnel to assign this report.';
      }

      populatePersonnelSelect(item.personnel_id || null);
    }

    async function loadPersonnel() {
      const data = await window.WG.apiGet('api/users/list.php?role=personnel');
      personnelList = data.items || [];
    }

    async function loadReportDetail() {
      const data = await window.WG.apiGet('api/reports/detail.php?id=' + reportId);
      if (!data.ok || !data.item) {
        throw new Error(data.error || 'Could not load report');
      }
      return data.item;
    }

    function renderReport(item) {
      currentReport = item;
      document.getElementById('res_id').textContent = '#' + item.id;

      const statusEl = document.getElementById('res_status');
      statusEl.textContent = String(item.status || '').toUpperCase();
      if (item.status === 'open') statusEl.style.color = 'var(--danger)';
      else if (item.status === 'resolved') statusEl.style.color = 'var(--ok)';
      else statusEl.style.color = 'var(--accent)';

      const priEl = document.getElementById('res_priority');
      priEl.textContent = '';
      priEl.appendChild(window.Priority.createBadge(item.priority || 'medium'));

      const slaEl = document.getElementById('res_sla');
      slaEl.textContent = '';
      if (window.SLA) {
        slaEl.appendChild(window.SLA.createBadge(item));
      }
      const dueEl = document.getElementById('res_due_at');
      dueEl.textContent = item.due_at ? 'Due at: ' + item.due_at : '';
      if (item.resolved_at) {
        dueEl.textContent += (dueEl.textContent ? ' · ' : '') + 'Resolved: ' + item.resolved_at;
      }

      document.getElementById('res_cat_area').textContent = item.category + ' | ' + item.area;
      document.getElementById('res_citizen').textContent =
        (item.citizen_name || 'Unknown') + ' (' + (item.citizen_email || '—') + ')';

      setAssignmentDisplay(item);
      document.getElementById('res_desc').textContent = item.description || '';
      document.getElementById('res_date').textContent = item.created_at || '';

      if (item.image_path) {
        const imgEl = document.getElementById('res_img');
        imgEl.src = window.BASE_URL + String(item.image_path).replace(/^\/+/, '');
        document.getElementById('imageContainer').style.display = 'block';
      } else {
        document.getElementById('imageContainer').style.display = 'none';
      }

      updateAssignUi(item);
      fillEditForm(item);
    }

    function fillEditForm(item) {
      document.getElementById('edit_description').value = item.description || '';
      document.getElementById('edit_priority').value = item.priority || 'medium';
      document.getElementById('edit_status').value = item.status || 'open';
      const catSel = document.getElementById('edit_category');
      const areaSel = document.getElementById('edit_area');
      catSel.textContent = '';
      areaSel.textContent = '';
      categories.forEach((c) => {
        const o = document.createElement('option');
        o.value = String(c.id);
        o.textContent = c.name;
        if (Number(c.id) === Number(item.category_id)) o.selected = true;
        catSel.appendChild(o);
      });
      areas.forEach((a) => {
        const o = document.createElement('option');
        o.value = String(a.id);
        o.textContent = a.name;
        if (Number(a.id) === Number(item.area_id)) o.selected = true;
        areaSel.appendChild(o);
      });
    }

    async function loadHistory() {
      const tbody = document.getElementById('historyBody');
      try {
        const data = await window.WG.apiGet('api/reports/assignment-history.php?report_id=' + reportId);
        const items = data.items || [];
        if (!items.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="muted-cell">No assignment history yet.</td></tr>';
          return;
        }
        tbody.textContent = '';
        items.forEach((h) => {
          const tr = document.createElement('tr');
          const cells = [
            h.assigned_at || '',
            h.old_personnel_name || '—',
            h.new_personnel_name || '—',
            h.assigned_by_name || '—',
          ];
          cells.forEach((text) => {
            const td = document.createElement('td');
            td.textContent = text;
            tr.appendChild(td);
          });
          tbody.appendChild(tr);
        });
      } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" class="danger-cell">Could not load history.</td></tr>';
      }
    }

    async function refreshReport() {
      hideAssignMsg();
      const item = await loadReportDetail();
      renderReport(item);
    }

    assignBtn.addEventListener('click', async () => {
      hideAssignMsg();
      const personnelId = parseInt(personnelSelect.value, 10);
      if (!personnelId) {
        showAssignMsg('Please select personnel.', false);
        return;
      }

      assignBtn.disabled = true;
      personnelSelect.disabled = true;

      try {
        await window.WG.apiPost('api/reports/assign.php', {
          report_id: reportId,
          personnel_id: personnelId,
        });
        notify('Personnel assigned successfully.', true);
        await refreshReport();
        await loadHistory();
      } catch (err) {
        showAssignMsg(err.message || 'Failed to assign personnel.', false);
      } finally {
        assignBtn.disabled = personnelList.length === 0;
        personnelSelect.disabled = personnelList.length === 0;
      }
    });

    document.getElementById('editReportForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('saveReportBtn');
      btn.classList.add('btn-loading');
      try {
        await window.WG.apiPost('api/reports/update.php', {
          report_id: reportId,
          description: document.getElementById('edit_description').value,
          category_id: parseInt(document.getElementById('edit_category').value, 10),
          area_id: parseInt(document.getElementById('edit_area').value, 10),
          status: document.getElementById('edit_status').value,
          priority: document.getElementById('edit_priority').value,
        });
        notify('Report updated.', true);
        await refreshReport();
      } catch (err) {
        notify(err.message || 'Update failed.', false);
      } finally {
        btn.classList.remove('btn-loading');
      }
    });

    document.getElementById('deleteReportBtn').addEventListener('click', async () => {
      if (!confirm('Soft-delete this report?')) return;
      try {
        await window.WG.apiPost('api/reports/delete.php', { report_id: reportId });
        notify('Report deleted.', true);
        window.location.href = window.BASE_URL + 'admin/reports.php';
      } catch (err) {
        notify(err.message || 'Delete failed.', false);
      }
    });

    (async () => {
      if (reportId <= 0) {
        DomSafe.setAlert(loading, 'Invalid Report ID.');
        return;
      }

      try {
        const [catData, areaData] = await Promise.all([
          window.WG.apiGet('api/categories/list.php'),
          window.WG.apiGet('api/areas/list.php'),
        ]);
        categories = catData.items || [];
        areas = areaData.items || [];
        await loadPersonnel();
        const item = await loadReportDetail();
        loading.style.display = 'none';
        document.getElementById('reportContent').style.display = 'block';
        renderReport(item);
        if (window.ReportTimeline) {
          window.ReportTimeline.load('reportTimeline', reportId);
        }
        await loadHistory();
      } catch (err) {
        DomSafe.setAlert(loading, err.message || 'Error loading report details.');
      }
    })();
  })();
</script>

<script src="<?= e(base_url('assets/js/report-timeline.js')) ?>?v=<?= e($timelineJsVersion) ?>"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
