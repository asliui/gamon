<?php

declare(strict_types=1);

// citizen/report-detail.php
// Shows a single report via /api/reports/detail.php?id=..., including the uploaded image.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'citizen') {
    redirect(base_url('dashboard.php'));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = 'Report Details';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel max-560">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Report Details</h1>
    <a href="<?= e(base_url('citizen/my-reports.php')) ?>" class="btn">Back</a>
  </div>
  <div class="spacer"></div>

  <div id="loading">Loading details...</div>
  
  <div id="reportContent" style="display: none;">
    <div class="kpi">
        <div class="label">Report ID</div>
        <div class="value" id="res_id"></div>
    </div>
    <div class="spacer"></div>
    
    <div class="field">
        <label>Status</label>
        <div id="res_status" style="font-weight: bold; font-size: 1.2rem;"></div>
    </div>

    <div class="field">
        <label>Category & Area</label>
        <div id="res_cat_area" style="color: var(--text);"></div>
    </div>

    <div class="field">
        <label>Description</label>
        <p id="res_desc" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; border: 1px solid var(--border); margin-top: 5px;"></p>
    </div>

    <div class="field" id="imageContainer" style="display: none; margin-top: 15px;">
        <label>Attached Photo</label>
        <img id="res_img" src="" alt="Waste Report Photo" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--border); margin-top: 5px;" />
    </div>

    <div class="field">
        <label>Reported Date</label>
        <div id="res_date" style="color: var(--muted); margin-top: 10px;"></div>
    </div>
  </div>
</div>

<script>
  (async () => {
    const reportId = <?= (int)$id ?>;
    try {
      const res = await fetch(window.BASE_URL + 'api/reports/detail.php?id=' + reportId, { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok) {
        document.getElementById('loading').innerHTML = '<div class="alert">Report not found.</div>';
        return;
      }

      const item = data.item;
      document.getElementById('res_id').textContent = '#' + item.id;
      
      const statusEl = document.getElementById('res_status');
      statusEl.textContent = item.status.toUpperCase();
      if (item.status === 'open') statusEl.style.color = 'var(--danger)';
      else if (item.status === 'resolved') statusEl.style.color = 'var(--ok)';
      else statusEl.style.color = 'var(--accent)';

      document.getElementById('res_cat_area').textContent = item.category + ' | ' + item.area;
      document.getElementById('res_desc').textContent = item.description;
      document.getElementById('res_date').textContent = item.created_at;
      
      // If the report has an image, display the image container
      if (item.image_path) {
          const imgEl = document.getElementById('res_img');
          imgEl.src = window.BASE_URL + item.image_path;
          document.getElementById('imageContainer').style.display = 'block';
      }
      
      document.getElementById('loading').style.display = 'none';
      document.getElementById('reportContent').style.display = 'block';

    } catch (err) {
      document.getElementById('loading').textContent = 'Error loading report details.';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>