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
  <?php
  $csrfToken = \WebGamon\Core\Csrf::token();
  $apiJsFile = dirname(__DIR__) . '/assets/js/api.js';
  $toastJsFile = dirname(__DIR__) . '/assets/js/toast.js';
  $apiJsVersion = file_exists($apiJsFile) ? (string)filemtime($apiJsFile) : '1';
  $toastJsVersion = file_exists($toastJsFile) ? (string)filemtime($toastJsFile) : '1';
  ?>
  <meta name="csrf-token" content="<?= e($csrfToken) ?>" />
  <script>
    window.BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
    window.CSRF_TOKEN = <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script src="<?= e(base_url('assets/js/dom-safe.js')) ?>"></script>
  <script src="<?= e(base_url('assets/js/api.js')) ?>?v=<?= e($apiJsVersion) ?>"></script>
  <script src="<?= e(base_url('assets/js/toast.js')) ?>?v=<?= e($toastJsVersion) ?>"></script>
</head>
<body>
  <div class="topbar" id="topbar">
    <div class="brand">Waste Management</div>
    <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="nav" id="mainNav">
      <a href="<?= e(base_url()) ?>">Home</a>
      <?php if ($user): ?>
        <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
        <a href="<?= e(base_url('account.php')) ?>">Account</a>
        <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
      <?php else: ?>
        <a href="<?= e(base_url('login.php')) ?>">Login</a>
        <a href="<?= e(base_url('register.php')) ?>">Register</a>
      <?php endif; ?>
    </nav>
  </div>
  <div class="container">

