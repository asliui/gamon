<?php

declare(strict_types=1);

// citizen/my-reports.php
// Lists citizen reports using /api/reports/list.php.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'My Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>My Reports</h1>
  <p>Loaded from <code>/api/reports/list.php</code>.</p>

  <div class="row">
    <a class="btn" href="<?= e(base_url('citizen/new-report.php')) ?>">New report</a>
  </div>

  <div class="spacer"></div>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto; max-height: 520px;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const res = await fetch(window.BASE_URL + 'api/reports/list.php?limit=100', { credentials: 'same-origin' });
    const data = await res.json();
    pre.textContent = JSON.stringify(data, null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

