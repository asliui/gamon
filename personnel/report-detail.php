<?php

declare(strict_types=1);

// personnel/report-detail.php
// Shows report details and allows personnel to assign open tasks to themselves.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();

// SECURITY: Only personnel can access this page
if (!$user || $user['role'] !== 'personnel') {
    redirect(base_url('dashboard.php'));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = 'Task Details';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel max-560">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Task Details</h1>
    <a href="<?= e(base_url('personnel/index.php')) ?>" class="btn">Back</a>
  </div>
  <div class="spacer"></div>

  <div id="loading">Loading details...</div>
  
  <div id="reportContent" style="display: none;">
    <div id="actionArea" style="margin-bottom: 20px; display: none;">
        <button id="assignBtn" class="btn" style="width: 100%; background: var(--accent); font-weight: bold; padding: 15px;">Assign to me</button>
    </div>

    <div class="kpi">
        <div class="label">Task ID</div>
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
        <label>Citizen Description</label>
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

<script src="<?= e(base_url('assets/js/reports.js')) ?>"></script>
<script>
  (async () => {
    const reportId = <?= (int)$id ?>;
    
    // Function to handle the assignment inside the detail page
    async function handleAssign() {
        if (!confirm('Are you sure you want to take this cleanup task?')) return;
        try {
            await window.Reports.apiPost('api/reports/assign.php', { report_id: reportId });
            alert('Task successfully assigned to you!');
            window.location.reload(); // Refresh to update status and hide button
        } catch (err) {
            alert(err.message || 'Failed to assign task.');
        }
    }

    try {
      const res = await fetch(window.BASE_URL + 'api/reports/detail.php?id=' + reportId, { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.ok) {
        document.getElementById('loading').innerHTML = '<div class="alert">Task not found.</div>';
        return;
      }

      const item = data.item;
      document.getElementById('res_id').textContent = '#' + item.id;
      
      const statusEl = document.getElementById('res_status');
      statusEl.textContent = item.status.toUpperCase();
      
      // Handle colors and "Assign" button visibility
      if (item.status === 'open') {
          statusEl.style.color = 'var(--danger)';
          // Show the assign button only if the task is still open
          const actionArea = document.getElementById('actionArea');
          const assignBtn = document.getElementById('assignBtn');
          actionArea.style.display = 'block';
          assignBtn.onclick = handleAssign;
      } else if (item.status === 'resolved') {
          statusEl.style.color = 'var(--ok)';
      } else {
          statusEl.style.color = 'var(--accent)';
      }

      document.getElementById('res_cat_area').textContent = item.category + ' | ' + item.area;
      document.getElementById('res_desc').textContent = item.description;
      document.getElementById('res_date').textContent = item.created_at;
      
      if (item.image_path) {
          const imgEl = document.getElementById('res_img');
          imgEl.src = window.BASE_URL + item.image_path;
          document.getElementById('imageContainer').style.display = 'block';
      }
      
      document.getElementById('loading').style.display = 'none';
      document.getElementById('reportContent').style.display = 'block';

    } catch (err) {
      document.getElementById('loading').textContent = 'Error loading task details.';
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>