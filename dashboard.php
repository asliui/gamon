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

<div class="panel">
  <span class="badge">Dashboard</span>
  <h1>Welcome, <?= e((string)$user['name']) ?></h1>
  <p>Your access level is currently set as: <strong><?= e(ucfirst($user['role'])) ?></strong>.</p>

  <div class="row" style="margin-top: 10px;">
    <?php if ($user['role'] === 'citizen'): ?>
        <a class="btn" href="<?= e(base_url('citizen/index.php')) ?>">Citizen Dashboard</a>
    <?php elseif ($user['role'] === 'personnel'): ?>
        <a class="btn" href="<?= e(base_url('personnel/index.php')) ?>">Personnel Dashboard</a>
    <?php elseif ($user['role'] === 'admin'): ?>
        <a class="btn" href="<?= e(base_url('admin/index.php')) ?>">Admin Dashboard</a>
    <?php endif; ?>
    
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