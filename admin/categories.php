<?php

declare(strict_types=1);

// admin/categories.php
// Admin page to list and create waste categories.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Categories';
require __DIR__ . '/../includes/header.php';
?>

<div class="grid cols-2">
  <div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <h2>Add New Category</h2>
      <span class="badge">Admin Action</span>
    </div>
    <p>Create a new classification for waste reports.</p>

    <div id="msg" class="alert" style="display:none; margin-bottom: 15px;"></div>
    
    <form id="categoryForm">
      <div class="field">
        <label for="name">Category Name</label>
        <input type="text" id="name" name="name" required placeholder="e.g., Electronic Waste" />
      </div>
      <div class="actions" style="margin-top: 15px;">
        <button class="btn" type="submit" style="width: 100%; background: var(--accent); font-weight: bold;">Create Category</button>
      </div>
    </form>
  </div>

  <div class="panel">
    <h2>Current Categories</h2>
    <div class="spacer"></div>
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border);">
            <th style="padding: 12px 8px;">ID</th>
            <th style="padding: 12px 8px;">Category Name</th>
          </tr>
        </thead>
        <tbody id="catBody">
          <tr><td colspan="2" style="padding: 12px 8px;">Loading...</td></tr>
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

  // Load existing categories
  async function loadCategories() {
    const tbody = document.getElementById('catBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/categories/list.php', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="2" style="padding: 12px 8px; color: var(--muted);">No categories found.</td></tr>';
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
      tbody.innerHTML = '<tr><td colspan="2" style="padding: 12px 8px; color: var(--danger);">Failed to load categories.</td></tr>';
    }
  }

  // Handle new category submission
  document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const catName = form.name.value;

    try {
      await window.Reports.apiPost('api/categories/create.php', { name: catName });
      showMsg('Category created successfully!');
      form.reset();
      loadCategories(); // Reload the table dynamically
    } catch (err) {
      showMsg(err.message || 'Failed to create category.', true);
    }
  });

  // Initial load
  loadCategories();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>