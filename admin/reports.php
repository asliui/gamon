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
  <h1>Reports</h1>
  <p>Overview of all submitted reports across the system.</p>

  <form method="get" class="panel" style="margin: 12px 0;">
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
      <div class="field" style="margin: 0;">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="">All</option>
          <option value="open">Open</option>
          <option value="assigned">Assigned</option>
          <option value="in_progress">In progress</option>
          <option value="resolved">Resolved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="field" style="margin: 0;">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">All</option>
        </select>
      </div>
      <div class="field" style="margin: 0;">
        <label for="area_id">Area</label>
        <select id="area_id" name="area_id">
          <option value="">All</option>
        </select>
      </div>
      <div class="actions" style="margin: 0;">
        <button class="btn" type="submit">Filter</button>
        <a class="btn" href="<?= e(base_url('admin/reports.php')) ?>">Reset</a>
      </div>
    </div>
  </form>

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
    const qs = new URLSearchParams(window.location.search);
    const status = qs.get('status') || '';
    const categoryId = qs.get('category_id') || '';
    const areaId = qs.get('area_id') || '';

    const statusSelect = document.getElementById('status');
    const categorySelect = document.getElementById('category_id');
    const areaSelect = document.getElementById('area_id');

    if (statusSelect) statusSelect.value = status;

    async function fillSelect(select, url, selectedValue) {
      const res = await fetch(window.BASE_URL + url, { credentials: 'same-origin' });
      const data = await res.json();
      for (const item of (data.items || [])) {
        const opt = document.createElement('option');
        opt.value = String(item.id);
        opt.textContent = item.name;
        select.appendChild(opt);
      }
      if (selectedValue) {
        select.value = selectedValue;
      }
    }

    try {
      await Promise.all([
        fillSelect(categorySelect, 'api/categories/list.php', categoryId),
        fillSelect(areaSelect, 'api/areas/list.php', areaId),
      ]);

      const apiQs = new URLSearchParams();
      apiQs.set('limit', '100');
      if (status) apiQs.set('status', status);
      if (categoryId) apiQs.set('category_id', categoryId);
      if (areaId) apiQs.set('area_id', areaId);

      const res = await fetch(window.BASE_URL + 'api/reports/list.php?' + apiQs.toString(), { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="padding: 12px 8px; color: var(--muted);">No reports found.</td></tr>';
        return;
      }

      tbody.innerHTML = '';
      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        const viewUrl = `${window.BASE_URL}api/reports/detail.php?id=${encodeURIComponent(String(item.id))}`;

        tr.innerHTML = `
          <td style="padding: 12px 8px; color: var(--muted);">#${item.id}</td>
          <td style="padding: 12px 8px;"><strong>${item.category}</strong><br><small style="color:var(--muted)">${item.area}</small></td>
          <td style="padding: 12px 8px; font-weight: bold; font-size: 12px;">${String(item.status || '').toUpperCase()}</td>
          <td style="padding: 12px 8px;">
            <a href="${viewUrl}" class="btn" style="padding: 6px 10px; font-size: 12px;" target="_blank" rel="noopener">View (JSON)</a>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="4" style="padding: 12px 8px; color: var(--danger);">Failed to load data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>