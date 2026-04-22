<?php

declare(strict_types=1);

// citizen/report-detail.php
// Shows a single report via /api/reports/detail.php?id=...

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = 'Report Detail';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Report Detail</h1>
  <p>URL example: <code>/citizen/report-detail.php?id=1</code></p>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const id = <?= (int)$id ?>;
    const res = await fetch(window.BASE_URL + 'api/reports/detail.php?id=' + encodeURIComponent(id), { credentials: 'same-origin' });
    pre.textContent = JSON.stringify(await res.json(), null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

