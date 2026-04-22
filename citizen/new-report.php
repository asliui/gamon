<?php

declare(strict_types=1);

// citizen/new-report.php
// Minimal report creation page using fetch() to JSON APIs.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'New Report';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel max-560">
  <h1>New Waste Report</h1>
  <p>This form calls <code>/api/reports/create.php</code>.</p>

  <div id="msg" class="alert" style="display:none;"></div>
  <div class="spacer"></div>

  <form id="reportForm">
    <div class="field">
      <label for="category_id">Category</label>
      <select id="category_id" name="category_id" required></select>
    </div>
    <div class="field">
      <label for="area_id">Area</label>
      <select id="area_id" name="area_id" required></select>
    </div>
    <div class="field">
      <label for="description">Description</label>
      <textarea id="description" name="description" required></textarea>
    </div>
    <div class="actions">
      <button class="btn" type="submit">Submit report</button>
      <a class="btn" href="<?= e(base_url('citizen/my-reports.php')) ?>">My reports</a>
    </div>
  </form>
</div>

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  function showMsg(text, ok) {
    const el = document.getElementById('msg');
    el.style.display = 'block';
    el.classList.toggle('ok', !!ok);
    el.textContent = text;
  }

  async function fillSelect(select, url) {
    const data = await Reports.apiGet(url);
    select.innerHTML = '';
    for (const item of data.items || []) {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.name;
      select.appendChild(opt);
    }
  }

  (async () => {
    await fillSelect(document.getElementById('category_id'), 'api/categories/list.php');
    await fillSelect(document.getElementById('area_id'), 'api/areas/list.php');
  })();

  document.getElementById('reportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      category_id: parseInt(e.target.category_id.value, 10),
      area_id: parseInt(e.target.area_id.value, 10),
      description: e.target.description.value
    };
    try {
      const res = await Reports.apiPost('api/reports/create.php', payload);
      showMsg('Created report #' + res.report_id, true);
      e.target.description.value = '';
    } catch (err) {
      showMsg(err.message || 'Failed', false);
    }
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

