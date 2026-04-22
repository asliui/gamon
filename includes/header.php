<?php

declare(strict_types=1);

/** @var string $title */
/** @var array|null $user */

$title = $title ?? 'Waste Management';
$user = $user ?? null;

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title) ?></title>
  <!-- Keep CSS split for clarity. -->
  <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/layout.css')) ?>" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/forms.css')) ?>" />
  <link rel="stylesheet" href="<?= e(base_url('assets/css/responsive.css')) ?>" />
  <script>
    // Expose BASE_URL to vanilla JS (for fetch() + navigation).
    window.BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  </script>
</head>
<body>
  <div class="topbar">
    <div class="brand">Waste Management</div>
    <div class="nav">
      <a href="<?= e(base_url()) ?>">Home</a>
      <?php if ($user): ?>
        <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
        <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
      <?php else: ?>
        <a href="<?= e(base_url('login.php')) ?>">Login</a>
        <a href="<?= e(base_url('register.php')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="container">

