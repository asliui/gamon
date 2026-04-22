<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
$title = 'Personnel Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <span class="badge">Personnel</span>
  <h1>Personnel Dashboard</h1>
  <p>View open reports and manage assigned cleanup tasks.</p>

  <div class="row" style="margin-top: 12px;">
    <a class="btn" href="<?= e(base_url('personnel/open-reports.php')) ?>">Open reports</a>
    <a class="btn" href="<?= e(base_url('personnel/assigned-reports.php')) ?>">Assigned reports</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

