<?php

declare(strict_types=1);

// admin/users.php
// Admin page to list users and manage their roles via API.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Users Management';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Users Management</h1>
    <span class="badge">Admin View</span>
  </div>
  <p>Manage system users and their roles (Citizen, Personnel, Admin).</p>

  <div id="msg" class="alert" style="display:none; margin-bottom: 15px;"></div>
  
  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="border-bottom: 1px solid var(--border);">
          <th style="padding: 12px 8px;">ID</th>
          <th style="padding: 12px 8px;">Name</th>
          <th style="padding: 12px 8px;">Email</th>
          <th style="padding: 12px 8px;">Registered At</th>
          <th style="padding: 12px 8px;">Role</th>
        </tr>
      </thead>
      <tbody id="usersBody">
        <tr><td colspan="5" style="padding: 12px 8px;">Loading users...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  function showMsg(text, isError = false) {
    const msgEl = document.getElementById('msg');
    msgEl.style.display = 'block';
    msgEl.className = isError ? 'alert' : 'alert ok';
    msgEl.textContent = text;
    setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
  }

  async function updateRole(userId, selectElement) {
    const newRole = selectElement.value;
    selectElement.disabled = true; // prevent double clicks
    
    try {
      await window.Reports.apiPost('api/users/update-role.php', {
        user_id: userId,
        role: newRole
      });
      showMsg('User role updated successfully!');
    } catch (err) {
      showMsg(err.message || 'Failed to update role.', true);
      // Revert select back to previous state if failed
      selectElement.value = selectElement.getAttribute('data-original');
    } finally {
      selectElement.disabled = false;
      selectElement.setAttribute('data-original', selectElement.value);
    }
  }

  (async () => {
    const tbody = document.getElementById('usersBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/users/list.php', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" style="padding: 12px 8px; color: var(--muted);">No users found.</td></tr>';
          return;
      }

      tbody.innerHTML = '';
      const currentUserId = <?= (int)$user['id'] ?>;

      data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        // Dropdown for roles
        const isSelf = item.id === currentUserId;
        const disabledAttr = isSelf ? 'disabled title="You cannot change your own role"' : '';
        
        const roleSelect = `
          <select onchange="updateRole(${item.id}, this)" data-original="${item.role}" ${disabledAttr} style="padding: 6px; font-size: 13px; max-width: 120px;">
            <option value="citizen" ${item.role === 'citizen' ? 'selected' : ''}>Citizen</option>
            <option value="personnel" ${item.role === 'personnel' ? 'selected' : ''}>Personnel</option>
            <option value="admin" ${item.role === 'admin' ? 'selected' : ''}>Admin</option>
          </select>
        `;

        tr.innerHTML = `
          <td style="padding: 12px 8px;">#${item.id}</td>
          <td style="padding: 12px 8px; font-weight: bold;">${item.name}</td>
          <td style="padding: 12px 8px; color: var(--muted);">${item.email}</td>
          <td style="padding: 12px 8px; color: var(--muted); font-size: 13px;">${item.created_at}</td>
          <td style="padding: 12px 8px;">${roleSelect}</td>
        `;

        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="5" style="padding: 12px 8px; color: var(--danger);">Failed to load user data.</td></tr>';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>