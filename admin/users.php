<?php

declare(strict_types=1);

// admin/users.php
// Placeholder admin page. Real operations should be done via API endpoints.

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user || $user['role'] !== 'admin') {
    redirect(base_url('login.php'));
}

$title = 'Admin - Users';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Users</h1>
  <p>Placeholder. Add API endpoints for user management later.</p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

