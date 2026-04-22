<?php

declare(strict_types=1);

// personnel/open-reports.php
// Lists open reports using /api/reports/list.php?status=open

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'Open Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Open Reports</h1>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto; max-height: 520px;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const res = await fetch(window.BASE_URL + 'api/reports/list.php?status=open&limit=100', { credentials: 'same-origin' });
    pre.textContent = JSON.stringify(await res.json(), null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

