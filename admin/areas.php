<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Areas';
require __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-header">
  <div>
    <h1>Areas</h1>
    <p>Manage operational districts for waste reports and analytics.</p>
  </div>
  <span class="badge">Admin</span>
</div>

<div class="admin-grid">
  <div class="admin-card">
    <h2>Add New Area</h2>
    <p class="admin-card-desc">Define a zone personnel and citizens can select on reports.</p>
    <form id="areaForm" class="admin-form">
      <div class="field">
        <label for="name">Area name</label>
        <input type="text" id="name" name="name" required placeholder="e.g. Downtown North" autocomplete="off" />
      </div>
      <button class="btn btn-primary btn-block" type="submit" id="createAreaBtn">Create Area</button>
    </form>
  </div>

  <div class="admin-card admin-card--list">
    <h2>Current Areas</h2>
    <p class="admin-card-desc" id="areaCount">Loading…</p>
    <div class="entity-list-scroll">
      <div id="entityList" class="entity-list" aria-live="polite">
        <div class="entity-empty"><strong>Loading</strong><p>Please wait…</p></div>
      </div>
    </div>
  </div>
</div>

<script>
  function notify(msg, ok) {
    if (window.Toast) {
      ok ? window.Toast.success(msg) : window.Toast.error(msg);
    }
  }

  function setListLoading(loading) {
    document.getElementById('entityList').classList.toggle('is-loading', loading);
  }

  function renderEmpty(message, hint) {
    const list = document.getElementById('entityList');
    list.textContent = '';
    const box = document.createElement('div');
    box.className = 'entity-empty';
    const strong = document.createElement('strong');
    strong.textContent = message;
    const p = document.createElement('p');
    p.textContent = hint;
    box.append(strong, p);
    list.appendChild(box);
  }

  function renderError(message) {
    const list = document.getElementById('entityList');
    list.textContent = '';
    const box = document.createElement('div');
    box.className = 'entity-error';
    box.textContent = message;
    list.appendChild(box);
  }

  function buildRow(item, onSave, onDelete) {
    const row = document.createElement('div');
    row.className = 'entity-row';

    const idBadge = document.createElement('span');
    idBadge.className = 'entity-id';
    idBadge.textContent = '#' + item.id;

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'entity-name-input';
    input.value = item.name || '';
    input.setAttribute('aria-label', 'Area name for #' + item.id);

    const actions = document.createElement('div');
    actions.className = 'entity-actions';

    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'btn btn-small btn-primary';
    saveBtn.textContent = 'Save';
    saveBtn.addEventListener('click', async () => {
      const name = input.value.trim();
      if (!name) {
        notify('Area name cannot be empty.', false);
        return;
      }
      saveBtn.classList.add('btn-loading');
      saveBtn.disabled = true;
      try {
        await onSave(item.id, name);
      } finally {
        saveBtn.classList.remove('btn-loading');
        saveBtn.disabled = false;
      }
    });

    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-small btn-danger';
    delBtn.textContent = 'Delete';
    delBtn.addEventListener('click', () => onDelete(item.id));

    actions.append(saveBtn, delBtn);
    row.append(idBadge, input, actions);
    return row;
  }

  async function loadAreas() {
    const list = document.getElementById('entityList');
    const countEl = document.getElementById('areaCount');
    setListLoading(true);
    try {
      const data = await window.WG.apiGet('api/areas/list.php');
      const items = data.items || [];
      countEl.textContent = items.length + ' area' + (items.length === 1 ? '' : 's') + ' defined';

      if (items.length === 0) {
        renderEmpty('No areas yet', 'Use the form on the left to add your first area.');
        return;
      }

      list.textContent = '';
      items.forEach((item) => {
        list.appendChild(buildRow(item, updateArea, deleteArea));
      });
    } catch (err) {
      countEl.textContent = 'Could not load list';
      renderError(err.message || 'Failed to load areas.');
    } finally {
      setListLoading(false);
    }
  }

  async function updateArea(id, name) {
    try {
      await window.WG.apiPost('api/areas/update.php', { id, name });
      notify('Area saved successfully.', true);
      loadAreas();
    } catch (err) {
      const msg = err.message || 'Update failed.';
      notify(msg.includes('already exists') ? 'An area with this name already exists.' : msg, false);
    }
  }

  async function deleteArea(id) {
    if (!confirm('Delete this area? This cannot be undone.')) return;
    try {
      await window.WG.apiPost('api/areas/delete.php', { id });
      notify('Area deleted.', true);
      loadAreas();
    } catch (err) {
      const msg = err.message || 'Delete failed.';
      notify(
        msg.includes('used by active') || msg.includes('cannot be deleted')
          ? 'This area is used by active reports and cannot be deleted.'
          : msg,
        false
      );
    }
  }

  document.getElementById('areaForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('createAreaBtn');
    const name = form.name.value.trim();
    if (!name) {
      notify('Please enter an area name.', false);
      return;
    }
    btn.classList.add('btn-loading');
    btn.disabled = true;
    try {
      await window.WG.apiPost('api/areas/create.php', { name });
      notify('Area created successfully!', true);
      form.reset();
      loadAreas();
    } catch (err) {
      const msg = err.message || 'Failed to create area.';
      notify(msg.includes('already exists') ? 'An area with this name already exists.' : msg, false);
    } finally {
      btn.classList.remove('btn-loading');
      btn.disabled = false;
    }
  });

  loadAreas();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
