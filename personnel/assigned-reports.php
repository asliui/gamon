<?php

declare(strict_types=1);

// personnel/assigned-reports.php
// Placeholder. Add a dedicated API query later (join assignments).

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'Assigned Reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Assigned Reports</h1>
  <p>Placeholder. Implement <code>/api/reports/list.php?assigned_to=me</code> later.</p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

