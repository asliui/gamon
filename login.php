<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if ($user) {
    redirect(base_url('dashboard.php'));
}

$title = 'Login';
require __DIR__ . '/includes/header.php';
?>

<!-- login.php
     Minimal page. Actual auth happens via /api/auth/login.php (JSON).
-->
<div class="panel max-560">
  <h1>Login</h1>
  <p>Sign in to report or manage cleanup.</p>

  <div id="msg" class="alert" style="display:none;"></div>
  <div class="spacer"></div>

  <form id="loginForm">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="email" required />
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required />
    </div>

    <div class="actions">
      <button class="btn" type="submit">Login</button>
      <a class="btn" href="<?= e(base_url('register.php')) ?>">Create account</a>
    </div>
  </form>
</div>

<script src="<?= e(base_url('assets/js/auth.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>

