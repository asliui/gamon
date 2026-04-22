<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
$title = 'Citizen Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <span class="badge">Citizen</span>
  <h1>Citizen Dashboard</h1>
  <p>Report waste accumulation and track your reports.</p>

  <div class="row" style="margin-top: 12px;">
    <a class="btn" href="<?= e(base_url('citizen/new-report.php')) ?>">New report</a>
    <a class="btn" href="<?= e(base_url('citizen/my-reports.php')) ?>">My reports</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

