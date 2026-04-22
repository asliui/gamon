<?php

declare(strict_types=1);

// index.php
// Landing page for the Waste Management and Reporting System.

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
$title = 'Waste Management and Reporting System';

require __DIR__ . '/includes/header.php';
?>

<div class="grid cols-2">
  <div class="panel">
    <span class="badge">MVP Starter</span>
    <h1>Waste Management and Reporting System</h1>
    <p>
      Citizens report garbage accumulation, personnel handle cleanup, and admins monitor analytics.
    </p>
    <p>
      This starter is API-first: frontend pages call backend web services (JSON) via <code>fetch()</code>.
    </p>
    <?php if (!$user): ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top: 14px;">
        <a class="btn" href="<?= e(base_url('register.php')) ?>">Create account</a>
        <a class="btn" href="<?= e(base_url('login.php')) ?>">Login</a>
      </div>
    <?php else: ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top: 14px;">
        <a class="btn" href="<?= e(base_url('dashboard.php')) ?>">Go to dashboard</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h2>Status</h2>
    <?php if ($user): ?>
      <div class="kpi">
        <div class="label">Logged in as</div>
        <div class="value"><?= e($user['email']) ?></div>
      </div>
      <div style="height: 12px"></div>
      <div class="kpi">
        <div class="label">Role</div>
        <div class="value"><?= e($user['role']) ?></div>
      </div>
    <?php else: ?>
      <div class="alert">Not logged in.</div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

