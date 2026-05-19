// activity-log.js — Admin activity log viewer (vanilla JS).

(function () {
  const ACTIONS = [
    'report_created',
    'report_updated',
    'report_deleted',
    'report_priority_changed',
    'report_status_changed',
    'report_assigned',
    'assignment_progress_changed',
    'category_created',
    'category_updated',
    'category_deleted',
    'area_created',
    'area_updated',
    'area_deleted',
    'user_role_changed',
    'user_soft_deleted',
  ];

  const ENTITY_TYPES = ['report', 'assignment', 'category', 'area', 'user'];

  const ENTITY_BADGE = {
    report: 'log-badge-report',
    assignment: 'log-badge-assignment',
    category: 'log-badge-category',
    area: 'log-badge-area',
    user: 'log-badge-user',
  };

  let currentPage = 1;

  function formatDetails(raw) {
    if (raw === null || raw === undefined || raw === '') {
      return '—';
    }
    try {
      const obj = typeof raw === 'string' ? JSON.parse(raw) : raw;
      const text = JSON.stringify(obj);
      return text.length > 120 ? text.slice(0, 117) + '…' : text;
    } catch (e) {
      const text = String(raw);
      return text.length > 120 ? text.slice(0, 117) + '…' : text;
    }
  }

  function createBadge(text, className) {
    const span = document.createElement('span');
    span.className = 'log-badge ' + (className || '');
    span.textContent = text;
    return span;
  }

  function getFilters() {
    return {
      action: document.getElementById('log_filter_action').value,
      entity_type: document.getElementById('log_filter_entity_type').value,
      q: document.getElementById('log_filter_q').value.trim(),
    };
  }

  function buildQuery(filters, page) {
    const qs = new URLSearchParams();
    const perPage = parseInt(document.getElementById('logPerPageSelect').value, 10) || 20;
    qs.set('page', String(page));
    qs.set('per_page', String(perPage));
    if (filters.action) qs.set('action', filters.action);
    if (filters.entity_type) qs.set('entity_type', filters.entity_type);
    if (filters.q) qs.set('q', filters.q);
    return qs;
  }

  function updatePagination(res) {
    const bar = document.getElementById('logPaginationBar');
    const total = res.total ?? 0;
    const page = res.page ?? 1;
    const totalPages = res.total_pages ?? 0;

    bar.style.display = 'flex';
    document.getElementById('logPaginationSummary').textContent = 'Total entries: ' + total;
    document.getElementById('logPaginationPageLabel').textContent =
      totalPages > 0 ? 'Page ' + page + ' of ' + totalPages : 'Page 1 of 1';

    document.getElementById('logPrevPageBtn').disabled = page <= 1;
    document.getElementById('logNextPageBtn').disabled = totalPages === 0 || page >= totalPages;
    currentPage = page;
  }

  async function loadLogs(page) {
    if (page === undefined) page = currentPage;
    const tbody = document.getElementById('activityLogBody');
    tbody.innerHTML = '<tr><td colspan="6" class="muted-cell">Loading…</td></tr>';

    const filters = getFilters();
    try {
      const res = await window.WG.apiGet('api/admin/activity-log.php?' + buildQuery(filters, page).toString());
      updatePagination(res);

      const items = res.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="muted-cell">No activity log entries match your filters.</td></tr>';
        return;
      }

      tbody.textContent = '';
      items.forEach((item) => {
        const tr = document.createElement('tr');

        const tdDate = document.createElement('td');
        tdDate.className = 'log-cell-nowrap';
        tdDate.textContent = item.created_at || '';

        const tdUser = document.createElement('td');
        const userLabel = item.actor_name || item.actor_email || 'System';
        tdUser.textContent = userLabel;
        if (item.actor_email && item.actor_name) {
          const small = document.createElement('small');
          small.style.display = 'block';
          small.style.color = 'var(--muted)';
          small.textContent = item.actor_email;
          tdUser.appendChild(small);
        }

        const tdAction = document.createElement('td');
        tdAction.appendChild(createBadge(item.action || '', 'log-badge-action'));

        const tdType = document.createElement('td');
        const typeKey = String(item.entity_type || '');
        tdType.appendChild(createBadge(typeKey, ENTITY_BADGE[typeKey] || ''));

        const tdId = document.createElement('td');
        tdId.textContent = item.entity_id != null ? '#' + item.entity_id : '—';

        const tdDetails = document.createElement('td');
        tdDetails.className = 'log-details-cell';
        tdDetails.title = formatDetails(item.details);
        tdDetails.textContent = formatDetails(item.details);

        tr.append(tdDate, tdUser, tdAction, tdType, tdId, tdDetails);
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="danger-cell">' + (err.message || 'Failed to load activity log') + '</td></tr>';
      if (window.Toast) window.Toast.error(err.message || 'Failed to load activity log.');
    }
  }

  function initFilters() {
    const actionSel = document.getElementById('log_filter_action');
    const typeSel = document.getElementById('log_filter_entity_type');

    ACTIONS.forEach((a) => {
      const opt = document.createElement('option');
      opt.value = a;
      opt.textContent = a;
      actionSel.appendChild(opt);
    });

    ENTITY_TYPES.forEach((t) => {
      const opt = document.createElement('option');
      opt.value = t;
      opt.textContent = t;
      typeSel.appendChild(opt);
    });
  }

  function bindEvents() {
    document.getElementById('logApplyFiltersBtn').addEventListener('click', () => loadLogs(1));
    document.getElementById('logResetFiltersBtn').addEventListener('click', () => {
      document.getElementById('log_filter_action').value = '';
      document.getElementById('log_filter_entity_type').value = '';
      document.getElementById('log_filter_q').value = '';
      currentPage = 1;
      loadLogs(1);
    });
    document.getElementById('log_filter_q').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') loadLogs(1);
    });
    document.getElementById('logPrevPageBtn').addEventListener('click', () => {
      if (currentPage > 1) loadLogs(currentPage - 1);
    });
    document.getElementById('logNextPageBtn').addEventListener('click', () => loadLogs(currentPage + 1));
    document.getElementById('logPerPageSelect').addEventListener('change', () => loadLogs(1));
  }

  window.ActivityLogPage = {
    init() {
      initFilters();
      bindEvents();
      loadLogs(1);
    },
  };
})();
