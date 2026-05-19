<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Reports</h1>
  <p>Overview of all submitted reports. Use filters to narrow results.</p>

  <div class="row" style="margin-top: 12px; gap: 10px; flex-wrap: wrap;">
    <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Export CSV</a>
    <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Export JSON</a>
    <a class="btn" href="<?= e(base_url('api/exports/html.php')) ?>">Export HTML</a>
    <a class="btn" href="<?= e(base_url('admin/analytics.php')) ?>">Analytics</a>
    <a class="btn" href="<?= e(base_url('admin/import.php')) ?>">Import Data</a>
  </div>

  <div class="filter-bar">
    <div class="filter-bar-grid">
      <div class="field" style="margin:0;">
        <label for="filter_status">Status</label>
        <select id="filter_status">
          <option value="">All</option>
          <option value="open">Open</option>
          <option value="assigned">Assigned</option>
          <option value="in_progress">In progress</option>
          <option value="resolved">Resolved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="field" style="margin:0;">
        <label for="filter_sla_status">SLA status</label>
        <select id="filter_sla_status">
          <option value="">All</option>
          <option value="overdue">Overdue only</option>
          <option value="due_soon">Due soon</option>
          <option value="on_time">On time</option>
          <option value="resolved_late">Resolved late</option>
        </select>
      </div>
      <div class="field" style="margin:0;">
        <label for="filter_priority">Priority</label>
        <select id="filter_priority">
          <option value="">All</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>
      <div class="field" style="margin:0;">
        <label for="filter_category_id">Category</label>
        <select id="filter_category_id">
          <option value="">All</option>
        </select>
      </div>
      <div class="field" style="margin:0;">
        <label for="filter_area_id">Area</label>
        <select id="filter_area_id">
          <option value="">All</option>
        </select>
      </div>
      <div class="field" style="margin:0; grid-column: 1 / -1;">
        <label for="filter_q">Search</label>
        <input type="search" id="filter_q" placeholder="Description or report ID…" autocomplete="off" />
      </div>
    </div>
    <div class="filter-bar-actions" style="margin-top: 12px;">
      <button type="button" class="btn btn-primary" id="applyFiltersBtn">Apply filters</button>
      <button type="button" class="btn" id="resetFiltersBtn">Reset filters</button>
    </div>
  </div>

  <div class="list-pagination" id="paginationBar" style="display: none;">
    <div class="list-pagination-info">
      <span id="paginationSummary">Total reports: 0</span>
      <span id="paginationPageLabel" class="muted-text">Page 1 of 1</span>
    </div>
    <div class="list-pagination-controls">
      <label class="per-page-label" for="perPageSelect">Per page</label>
      <select id="perPageSelect" aria-label="Reports per page">
        <option value="10" selected>10</option>
        <option value="20">20</option>
        <option value="50">50</option>
      </select>
      <button type="button" class="btn" id="prevPageBtn" disabled>Previous</button>
      <button type="button" class="btn" id="nextPageBtn" disabled>Next</button>
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Category / Area</th>
          <th>Priority</th>
          <th>SLA</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="reportsBody">
        <tr><td colspan="6">Loading reports…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  let currentPage = 1;

  function fillSelect(select, items, selectedValue) {
    select.textContent = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = 'All';
    select.appendChild(allOpt);
    (items || []).forEach((item) => {
      const opt = document.createElement('option');
      opt.value = String(item.id);
      opt.textContent = item.name;
      select.appendChild(opt);
    });
    if (selectedValue) select.value = selectedValue;
  }

  function getFilters() {
    return {
      status: document.getElementById('filter_status').value,
      sla_status: document.getElementById('filter_sla_status').value,
      priority: document.getElementById('filter_priority').value,
      category_id: document.getElementById('filter_category_id').value,
      area_id: document.getElementById('filter_area_id').value,
      q: document.getElementById('filter_q').value.trim(),
    };
  }

  function buildQuery(filters, page) {
    const qs = new URLSearchParams();
    const perPage = parseInt(document.getElementById('perPageSelect').value, 10) || 10;
    qs.set('page', String(page));
    qs.set('per_page', String(perPage));
    if (filters.status) qs.set('status', filters.status);
    if (filters.sla_status) qs.set('sla_status', filters.sla_status);
    if (filters.priority) qs.set('priority', filters.priority);
    if (filters.category_id) qs.set('category_id', filters.category_id);
    if (filters.area_id) qs.set('area_id', filters.area_id);
    if (filters.q) qs.set('q', filters.q);
    return qs;
  }

  function updatePagination(res) {
    const bar = document.getElementById('paginationBar');
    const total = res.total ?? 0;
    const page = res.page ?? 1;
    const totalPages = res.total_pages ?? 0;
    const perPage = res.per_page ?? 10;

    bar.style.display = 'flex';
    document.getElementById('paginationSummary').textContent = 'Total reports: ' + total;
    const pageLabel = totalPages > 0 ? 'Page ' + page + ' of ' + totalPages : 'Page 1 of 1';
    document.getElementById('paginationPageLabel').textContent = pageLabel;

    document.getElementById('prevPageBtn').disabled = page <= 1;
    document.getElementById('nextPageBtn').disabled = totalPages === 0 || page >= totalPages;

    const sel = document.getElementById('perPageSelect');
    if (sel.value !== String(perPage)) {
      sel.value = String(perPage);
    }
    currentPage = page;
  }

  async function loadReports(page) {
    if (page === undefined) page = currentPage;
    const tbody = document.getElementById('reportsBody');
    tbody.innerHTML = '<tr><td colspan="6" class="muted-cell">Loading…</td></tr>';
    const filters = getFilters();
    try {
      const res = await window.WG.apiGet('api/reports/list.php?' + buildQuery(filters, page).toString());
      updatePagination(res);
      const items = res.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="muted-cell">No reports match your filters.</td></tr>';
        return;
      }
      tbody.textContent = '';
      items.forEach((item) => {
        const tr = document.createElement('tr');
        const tdId = document.createElement('td');
        tdId.textContent = '#' + item.id;
        const tdCat = document.createElement('td');
        const strong = document.createElement('strong');
        strong.textContent = item.category || '';
        tdCat.appendChild(strong);
        tdCat.appendChild(document.createElement('br'));
        const small = document.createElement('small');
        small.style.color = 'var(--muted)';
        small.textContent = item.area || '';
        tdCat.appendChild(small);
        const tdPri = document.createElement('td');
        tdPri.appendChild(window.Priority.createBadge(item.priority || 'medium'));
        const tdSla = document.createElement('td');
        if (window.SLA) {
          tdSla.appendChild(window.SLA.createBadge(item));
        }
        const tdStatus = document.createElement('td');
        tdStatus.style.fontWeight = 'bold';
        tdStatus.style.fontSize = '12px';
        tdStatus.textContent = String(item.status || '').toUpperCase();
        const tdAct = document.createElement('td');
        const link = document.createElement('a');
        link.href = window.BASE_URL + 'admin/report-detail.php?id=' + encodeURIComponent(String(item.id));
        link.className = 'btn btn-small';
        link.textContent = 'View';
        tdAct.appendChild(link);
        tr.append(tdId, tdCat, tdPri, tdSla, tdStatus, tdAct);
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" class="danger-cell">' + (err.message || 'Failed to load') + '</td></tr>';
    }
  }

  document.getElementById('applyFiltersBtn').addEventListener('click', () => loadReports(1));
  document.getElementById('resetFiltersBtn').addEventListener('click', () => {
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_sla_status').value = '';
    document.getElementById('filter_priority').value = '';
    document.getElementById('filter_category_id').value = '';
    document.getElementById('filter_area_id').value = '';
    document.getElementById('filter_q').value = '';
    currentPage = 1;
    loadReports(1);
  });
  document.getElementById('filter_q').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') loadReports(1);
  });
  document.getElementById('prevPageBtn').addEventListener('click', () => {
    if (currentPage > 1) loadReports(currentPage - 1);
  });
  document.getElementById('nextPageBtn').addEventListener('click', () => {
    loadReports(currentPage + 1);
  });
  document.getElementById('perPageSelect').addEventListener('change', () => loadReports(1));

  (async () => {
    const qs = new URLSearchParams(window.location.search);
    const [catData, areaData] = await Promise.all([
      window.WG.apiGet('api/categories/list.php'),
      window.WG.apiGet('api/areas/list.php'),
    ]);
    fillSelect(document.getElementById('filter_category_id'), catData.items, qs.get('category_id') || '');
    fillSelect(document.getElementById('filter_area_id'), areaData.items, qs.get('area_id') || '');
    if (qs.get('status')) document.getElementById('filter_status').value = qs.get('status');
    if (qs.get('sla_status')) document.getElementById('filter_sla_status').value = qs.get('sla_status');
    if (qs.get('priority')) document.getElementById('filter_priority').value = qs.get('priority');
    if (qs.get('q')) document.getElementById('filter_q').value = qs.get('q');
    loadReports(1);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
