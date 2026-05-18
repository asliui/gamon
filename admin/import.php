<?php

declare(strict_types=1);

// admin/import.php — Admin data import (JSON / CSV).

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Import Data';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <div>
      <h1>Import Data</h1>
      <p>Upload JSON or CSV files to import categories, areas, or reports. Existing export formats are supported.</p>
    </div>
    <a class="btn" href="<?= e(base_url('admin/index.php')) ?>">Back to Admin</a>
  </div>

  <div class="spacer"></div>

  <div id="msg" class="alert" style="display:none;"></div>

  <div class="grid cols-2 import-grid">
    <div class="panel">
      <h2>JSON Import</h2>
      <p class="hint">Use <code>{"items":[...]}</code> or a top-level array. Max 500 rows per file.</p>
      <form id="jsonImportForm" enctype="multipart/form-data">
        <div class="field">
          <label for="json_entity">Entity</label>
          <select id="json_entity" name="entity" required>
            <option value="categories">Categories</option>
            <option value="areas">Areas</option>
            <option value="reports">Reports</option>
          </select>
        </div>
        <div class="field">
          <label for="json_file">JSON file</label>
          <input type="file" id="json_file" name="file" accept=".json,application/json" required />
        </div>
        <div class="actions">
          <button class="btn" type="submit">Import JSON</button>
        </div>
      </form>
      <p class="hint" style="margin-top: 12px;">
        Categories/areas: <code>{"items":[{"name":"Recyclables"}]}</code><br />
        Reports: include <code>citizen_id</code>, <code>category_id</code>, <code>area_id</code>; optional <code>description</code>, <code>status</code>.
      </p>
    </div>

    <div class="panel">
      <h2>CSV Import</h2>
      <p class="hint">First row must be column headers. Reports CSV may match export columns plus optional <code>description</code>.</p>
      <form id="csvImportForm" enctype="multipart/form-data">
        <div class="field">
          <label for="csv_entity">Entity</label>
          <select id="csv_entity" name="entity" required>
            <option value="categories">Categories</option>
            <option value="areas">Areas</option>
            <option value="reports">Reports</option>
          </select>
        </div>
        <div class="field">
          <label for="csv_file">CSV file</label>
          <input type="file" id="csv_file" name="file" accept=".csv,text/csv" required />
        </div>
        <div class="actions">
          <button class="btn" type="submit">Import CSV</button>
        </div>
      </form>
      <p class="hint" style="margin-top: 12px;">
        Categories/areas header: <code>name</code><br />
        Reports headers: <code>citizen_id,category_id,area_id,status,created_at,description</code>
      </p>
    </div>
  </div>

  <div class="spacer"></div>
  <div class="panel">
    <h2>Export (reference)</h2>
    <div class="row">
      <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Download CSV export</a>
      <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Download JSON export</a>
    </div>
  </div>
</div>

<script>
  function showMsg(text, ok) {
    const el = document.getElementById('msg');
    el.style.display = 'block';
    el.classList.toggle('ok', !!ok);
    el.textContent = text;
  }

  async function submitImport(form, endpoint) {
    const formData = new FormData(form);
    const res = await fetch(window.BASE_URL + endpoint, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': window.WG ? window.WG.csrfToken() : (window.CSRF_TOKEN || '') },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) {
      throw new Error(data.error || 'Import failed');
    }
    const errCount = (data.errors || []).length;
    let msg = `Imported ${data.inserted} row(s), skipped ${data.skipped}.`;
    if (errCount > 0) {
      msg += ' ' + errCount + ' validation issue(s): ' + (data.errors || []).slice(0, 3).join('; ');
    }
    showMsg(msg, true);
    form.reset();
  }

  document.getElementById('jsonImportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await submitImport(e.target, 'api/imports/json.php');
    } catch (err) {
      showMsg(err.message || 'JSON import failed', false);
    }
  });

  document.getElementById('csvImportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await submitImport(e.target, 'api/imports/csv.php');
    } catch (err) {
      showMsg(err.message || 'CSV import failed', false);
    }
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
