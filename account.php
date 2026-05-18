<?php

declare(strict_types=1);

// account.php — Profile and self-service account deletion.

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$loginDeletedUrl = base_url('login.php?account_deleted=1');
$deleteApiUrl = base_url('api/auth/delete-account.php');

$title = 'My Account';
require __DIR__ . '/includes/header.php';
?>

<div class="panel max-560">
  <h1>My Account</h1>
  <p>View your profile or permanently delete your account from the system.</p>

  <div id="msg" class="alert" style="display:none;"></div>

  <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
    <div class="kpi">
      <div class="label">Name</div>
      <div class="value"><?= e((string)$user['name']) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Role</div>
      <div class="value" style="text-transform: capitalize;"><?= e((string)$user['role']) ?></div>
    </div>
    <div class="kpi" style="grid-column: 1 / -1;">
      <div class="label">Email</div>
      <div class="value"><?= e((string)$user['email']) ?></div>
    </div>
  </div>

  <div class="spacer"></div>

  <div class="panel" style="border-color: rgba(255,90,106,0.35);">
    <h2 style="color: var(--danger); margin-top: 0;">Delete account</h2>
    <p class="hint">
      Your reports will remain in the system for history, but your name will appear as
      <strong>Deleted user</strong>. This action cannot be undone.
      <?php if ($user['role'] === 'admin'): ?>
        If you are the last active admin, deletion is blocked.
      <?php endif; ?>
    </p>

    <form id="deleteAccountForm">
      <input type="hidden" data-csrf-token="<?= e(\WebGamon\Core\Csrf::token()) ?>" />
      <div class="field">
        <label for="confirm">Type <code>DELETE MY ACCOUNT</code> to confirm</label>
        <input type="text" id="confirm" name="confirm" autocomplete="off" required placeholder="DELETE MY ACCOUNT" />
      </div>
      <div class="actions">
        <button class="btn danger" type="submit" id="deleteSubmitBtn">Delete my account</button>
        <a class="btn" href="<?= e(base_url('dashboard.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
  // Built by PHP — never double-prefix BASE_URL in JS.
  const LOGIN_DELETED_URL = <?= json_encode($loginDeletedUrl, JSON_UNESCAPED_SLASHES) ?>;
  const DELETE_ACCOUNT_API = <?= json_encode($deleteApiUrl, JSON_UNESCAPED_SLASHES) ?>;

  function showMsg(text, ok) {
    const el = document.getElementById('msg');
    el.style.display = 'block';
    el.classList.toggle('ok', !!ok);
    el.textContent = text;
  }

  function getCsrfToken() {
    if (window.CSRF_TOKEN) return String(window.CSRF_TOKEN);
    const el = document.querySelector('[data-csrf-token]');
    return el ? String(el.getAttribute('data-csrf-token') || '') : '';
  }

  document.getElementById('deleteAccountForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const confirmText = e.target.confirm.value.trim();
    if (confirmText !== 'DELETE MY ACCOUNT') {
      showMsg('You must type DELETE MY ACCOUNT exactly.', false);
      return;
    }
    if (!window.confirm('This will permanently delete your account. Continue?')) {
      return;
    }

    const token = getCsrfToken();
    if (!token) {
      showMsg('Security token missing. Please refresh the page and try again.', false);
      return;
    }

    const btn = document.getElementById('deleteSubmitBtn');
    if (btn) btn.disabled = true;

    try {
      const res = await fetch(DELETE_ACCOUNT_API, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({ confirm: confirmText }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        throw new Error(data.error || 'Could not delete account.');
      }

      console.log('delete redirect target:', LOGIN_DELETED_URL);
      window.location.replace(LOGIN_DELETED_URL);
    } catch (err) {
      if (btn) btn.disabled = false;
      showMsg(err.message || 'Could not delete account.', false);
    }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
