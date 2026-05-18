<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if ($user) {
    redirect(base_url('dashboard.php'));
}

$title = 'Register';
require __DIR__ . '/includes/header.php';
?>

<!-- register.php
     Minimal page. Actual auth happens via /api/auth/register.php (JSON).
-->
<div class="panel max-560">
  <h1>Register</h1>
  <p>Create an account (default role: citizen).</p>

  <div id="msg" class="alert" style="display:none;"></div>
  <div class="spacer"></div>

  <form id="registerForm">
    <input type="hidden" data-csrf-token="<?= e(\WebGamon\Core\Csrf::token()) ?>" />
    <div class="field">
      <label for="name">Name</label>
      <input id="name" name="name" autocomplete="name" required />
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="email" required />
    </div>
    <p class="hint">New accounts are registered as <strong>citizens</strong>. Admins can change roles later.</p>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required />
    </div>

    <div class="actions">
      <button class="btn" type="submit">Create account</button>
      <a class="btn" href="<?= e(base_url('login.php')) ?>">I already have an account</a>
    </div>
  </form>
</div>

<?php
$authJsFile = __DIR__ . '/assets/js/auth.js';
$authJsVersion = file_exists($authJsFile) ? (string)filemtime($authJsFile) : '1';
?>
<script src="<?= e(base_url('assets/js/auth.js')) ?>?v=<?= e($authJsVersion) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>

