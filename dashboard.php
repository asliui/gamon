<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<!-- dashboard.php
     Minimal dashboard that calls the session API and shows JSON.
-->
<div class="panel">
  <span class="badge">Dashboard</span>
  <h1>Welcome, <?= e((string)$user['name']) ?></h1>
  <p>This page demonstrates API usage via <code>fetch()</code>.</p>

  <div class="row" style="margin-top: 10px;">
    <a class="btn" href="<?= e(base_url('citizen/index.php')) ?>">Citizen</a>
    <a class="btn" href="<?= e(base_url('personnel/index.php')) ?>">Personnel</a>
    <a class="btn" href="<?= e(base_url('admin/index.php')) ?>">Admin</a>
    <a class="btn danger" href="<?= e(base_url('logout.php')) ?>">Logout</a>
  </div>

  <div class="spacer"></div>
  <h2>Session JSON</h2>
  <pre id="sessionJson" class="panel" style="white-space: pre-wrap; overflow:auto; max-height: 320px;"></pre>
</div>

<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
<script>
  (async () => {
    const pre = document.getElementById('sessionJson');
    const data = await window.WM.getSession();
    pre.textContent = JSON.stringify(data, null, 2);
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

