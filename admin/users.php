<?php

declare(strict_types=1);

// admin/users.php — List users, manage roles, soft-delete accounts.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Users Management';
$adminEmail = (string)$user['email'];
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <div>
      <h1>Users Management</h1>
      <p>Manage roles and remove accounts. Deleted users are hidden from this list.</p>
    </div>
    <span class="badge">Admin View</span>
  </div>

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
          <th style="padding: 12px 8px;">Actions</th>
        </tr>
      </thead>
      <tbody id="usersBody">
        <tr><td colspan="6" style="padding: 12px 8px;">Loading users...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  console.log('CSRF token:', window.CSRF_TOKEN);

  const CURRENT_USER_ID = <?= (int)$user['id'] ?>;
  const ADMIN_EMAIL = <?= json_encode($adminEmail, JSON_UNESCAPED_SLASHES) ?>;

  function showMsg(text, isError = false) {
    const msgEl = document.getElementById('msg');
    msgEl.style.display = 'block';
    msgEl.className = isError ? 'alert' : 'alert ok';
    msgEl.textContent = text;
    setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
  }

  async function updateRole(userId, selectElement) {
    const newRole = selectElement.value;
    selectElement.disabled = true;

    try {
      await window.Reports.apiPost('api/users/update-role.php', {
        user_id: userId,
        role: newRole,
      });
      showMsg('User role updated successfully!');
    } catch (err) {
      showMsg(err.message || 'Failed to update role.', true);
      selectElement.value = selectElement.getAttribute('data-original');
    } finally {
      selectElement.disabled = false;
      selectElement.setAttribute('data-original', selectElement.value);
    }
  }

  async function deleteUser(item) {
    const isSelf = item.id === CURRENT_USER_ID;
    let confirmValue;

    if (isSelf) {
      const promptMsg = 'You are deleting YOUR admin account.\nType your exact email to confirm:\n' + item.email;
      confirmValue = window.prompt(promptMsg);
      if (confirmValue === null) return;
      if (confirmValue.trim().toLowerCase() !== String(item.email).toLowerCase()) {
        showMsg('Email did not match. Account not deleted.', true);
        return;
      }
    } else {
      if (!window.confirm('Delete account for ' + item.name + ' (' + item.email + ')? Reports will be kept.')) {
        return;
      }
      confirmValue = window.prompt('Type DELETE to confirm removal of this account:');
      if (confirmValue === null) return;
      if (confirmValue !== 'DELETE') {
        showMsg('You must type DELETE exactly.', true);
        return;
      }
    }

    try {
      const data = await window.Reports.apiPost('api/users/delete.php', {
        user_id: item.id,
        confirm: confirmValue.trim(),
      });
      if (isSelf) {
        const loginDeleted = <?= json_encode(base_url('login.php?account_deleted=1'), JSON_UNESCAPED_SLASHES) ?>;
        console.log('delete redirect target:', loginDeleted);
        window.location.replace(loginDeleted);
        return;
      }
      showMsg('User #' + data.deleted_user_id + ' deleted.');
      loadUsers();
    } catch (err) {
      showMsg(err.message || 'Failed to delete user.', true);
    }
  }

  async function loadUsers() {
    const tbody = document.getElementById('usersBody');
    try {
      const res = await fetch(window.BASE_URL + 'api/users/list.php', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok || !data.items || data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--muted);">No active users found.</td></tr>';
        return;
      }

      tbody.textContent = '';

      data.items.forEach((item) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        const isSelf = item.id === CURRENT_USER_ID;
        const roles = ['citizen', 'personnel', 'admin'];
        const select = document.createElement('select');
        select.style.cssText = 'padding: 6px; font-size: 13px; max-width: 120px;';
        select.dataset.original = item.role;
        if (isSelf) {
          select.disabled = true;
          select.title = 'You cannot change your own role';
        } else {
          select.addEventListener('change', () => updateRole(item.id, select));
        }
        roles.forEach((role) => {
          const opt = document.createElement('option');
          opt.value = role;
          opt.textContent = role.charAt(0).toUpperCase() + role.slice(1);
          if (item.role === role) opt.selected = true;
          select.appendChild(opt);
        });

        function cell(text, style) {
          const td = document.createElement('td');
          td.style.padding = '12px 8px';
          if (style) Object.assign(td.style, style);
          td.textContent = text ?? '';
          return td;
        }

        const tdRole = document.createElement('td');
        tdRole.style.padding = '12px 8px';
        tdRole.appendChild(select);

        const tdAct = document.createElement('td');
        tdAct.style.padding = '12px 8px';
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn danger';
        delBtn.style.cssText = 'padding: 6px 10px; font-size: 12px;';
        delBtn.textContent = isSelf ? 'Delete me' : 'Delete';
        delBtn.title = isSelf
          ? 'Requires typing your email address'
          : 'Soft-deletes account; reports are preserved';
        delBtn.addEventListener('click', () => deleteUser(item));
        tdAct.appendChild(delBtn);

        tr.append(
          cell('#' + item.id),
          cell(item.name, { fontWeight: 'bold' }),
          cell(item.email, { color: 'var(--muted)' }),
          cell(item.created_at, { color: 'var(--muted)', fontSize: '13px' }),
          tdRole,
          tdAct
        );
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" style="padding: 12px 8px; color: var(--danger);">Failed to load user data.</td></tr>';
    }
  }

  loadUsers();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
