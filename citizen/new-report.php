<?php

declare(strict_types=1);

// citizen/new-report.php
// Minimal report creation page using fetch() with FormData to support image uploads.

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
  <p>Submit a new issue. You can optionally attach a photo of the waste.</p>

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
      <textarea id="description" name="description" required placeholder="Describe the waste issue in detail..."></textarea>
    </div>
    
    <div class="field">
      <label for="image">Photo (Optional - JPG/PNG only)</label>
      <input type="file" id="image" name="image" accept="image/png, image/jpeg" style="background: transparent; border: 1px dashed var(--border); padding: 20px; cursor: pointer;" />
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

  // Updated to use FormData for handling file uploads natively via AJAX
  document.getElementById('reportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    
    // FormData automatically packages all form inputs, including the file
    const formData = new FormData(form);

    try {
      const res = await fetch(window.BASE_URL + 'api/reports/create.php', {
        method: 'POST',
        body: formData, // Sending multipart/form-data directly
        credentials: 'same-origin'
      });
      
      const data = await res.json();
      
      if (!res.ok) {
         throw new Error(data.error || 'Failed to submit report');
      }

      showMsg('Success! Created report #' + data.report_id, true);
      form.reset();
    } catch (err) {
      showMsg(err.message, false);
    }
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>