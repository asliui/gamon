<?php

declare(strict_types=1);

// admin/areas.php
// Shows areas via API.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Areas';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Areas</h1>
  <p>Read-only list from <code>/api/areas/list.php</code>.</p>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const res = await fetch(window.BASE_URL + 'api/areas/list.php');
    pre.textContent = JSON.stringify(await res.json(), null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

