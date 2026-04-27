<?php

declare(strict_types=1);

// admin/areas.php
// Admin page to list and manage cleanup areas/districts.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Areas';
require __DIR__ . '/../includes/header.php';
?>

<div class="grid cols-2">
  <div class="panel">
    <h2>Add New Area</h2>
    <p>Define a new operational district for waste management.</p>

    <div id="msg" class="alert" style="display:none; margin-bottom: 15px;"></div>
    
    <form id="areaForm">
      <div class="field">
        <label for="name">Area Name</label>
        <input type="text" id="name" name="name" required placeholder="e.g., Downtown North" />
      </div>
      <div class="actions" style="margin-top: 15px;">
        <button class="btn" type="submit" style="width: 100%; background: var(--accent); font-weight: bold;">Create Area</button>
      </div>
    </form>
  </div>

  <div class="panel">
    <h2>Current Areas</h2>
    <div class="spacer"></div>
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border);">
            <th style="padding: 12px 8px;">ID</th>
            <th style="padding: 12px 8px;">Area Name</th>
          </tr>
        </thead>
        <tbody id="areaBody">
          <tr><td colspan="2" style="padding: 12px 8px;">Loading areas...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  function showMsg(text, isError = false) {
    const msgEl = document.getElementById('msg');
    msgEl.style.display = 'block';
    msgEl.className = isError ? 'alert' : 'alert ok';
    msgEl.textContent = text;
    setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
  }

  async function loadAreas() {
    const tbody = document.getElementById('areaBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/areas/list.php', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="2" style="padding: 12px 8px; color: var(--muted);">No areas defined.</td></tr>';
          return;
      }

      tbody.innerHTML = '';
      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        tr.innerHTML = `
          <td style="padding: 12px 8px; color: var(--muted);">#${item.id}</td>
          <td style="padding: 12px 8px; font-weight: bold;">${item.name}</td>
        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="2" style="padding: 12px 8px; color: var(--danger);">Failed to load areas.</td></tr>';
    }
  }

  document.getElementById('areaForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    try {
      await window.Reports.apiPost('api/areas/create.php', { name: form.name.value });
      showMsg('Area created successfully!');
      form.reset();
      loadAreas();
    } catch (err) {
      showMsg(err.message || 'Failed to create area.', true);
    }
  });

  loadAreas();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>