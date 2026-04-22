<?php

declare(strict_types=1);

// admin/analytics.php
// Minimal analytics page calling API endpoints.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Analytics</h1>
  <p>Calls <code>/api/analytics/*</code> endpoints and shows JSON.</p>

  <div class="row">
    <a class="btn" href="<?= e(base_url('api/exports/csv.php')) ?>">Export CSV</a>
    <a class="btn" href="<?= e(base_url('api/exports/json.php')) ?>">Export JSON</a>
    <a class="btn" href="<?= e(base_url('api/exports/html.php')) ?>">Export HTML</a>
  </div>

  <div class="spacer"></div>
  <pre id="data" class="panel" style="white-space: pre-wrap; overflow:auto; max-height: 520px;"></pre>
</div>

<script>
  (async () => {
    const pre = document.getElementById('data');
    const endpoints = [
      window.BASE_URL + 'api/analytics/summary.php',
      window.BASE_URL + 'api/analytics/by-area.php',
      window.BASE_URL + 'api/analytics/by-category.php',
      window.BASE_URL + 'api/analytics/cleanest-dirtiest.php',
    ];
    const out = {};
    for (const url of endpoints) {
      const res = await fetch(url, { credentials: 'same-origin' });
      out[url] = await res.json();
    }
    pre.textContent = JSON.stringify(out, null, 2);
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

