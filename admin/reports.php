<?php

declare(strict_types=1);

// admin/reports.php
// Shows reports list via API.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Reports</h1>
  <p>Data from <code>/api/reports/list.php</code>.</p>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto; max-height: 420px;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const res = await fetch(window.BASE_URL + 'api/reports/list.php?limit=50', { credentials: 'same-origin' });
    pre.textContent = JSON.stringify(await res.json(), null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

