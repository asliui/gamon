<?php

declare(strict_types=1);

// dashboard.php
// Main landing page after login, displaying user-specific info and navigation.

require_once __DIR__ . '/core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}

$title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div style="display: flex; justify-content: space-between; align-items: center;">
      <div>
          <span class="badge">Welcome Back</span>
          <h1 style="margin-top: 5px;">Hello, <?= e((string)$user['name']) ?>!</h1>
      </div>
      <a class="btn danger" href="<?= e(base_url('logout.php')) ?>">Logout</a>
  </div>
  
  <p>You are currently logged into the GaMon Waste Management System.</p>

  <div class="row" style="margin-top: 20px;">
    <?php if ($user['role'] === 'citizen'): ?>
        <a class="btn" style="background: var(--accent); font-weight: bold; padding: 12px 24px;" href="<?= e(base_url('citizen/index.php')) ?>">Open Citizen Portal</a>
    <?php elseif ($user['role'] === 'personnel'): ?>
        <a class="btn" style="background: var(--accent); font-weight: bold; padding: 12px 24px;" href="<?= e(base_url('personnel/index.php')) ?>">Open Personnel Portal</a>
    <?php elseif ($user['role'] === 'admin'): ?>
        <a class="btn" style="background: var(--accent); font-weight: bold; padding: 12px 24px;" href="<?= e(base_url('admin/index.php')) ?>">Open Admin Control Panel</a>
    <?php endif; ?>
  </div>

  <div class="spacer" style="height: 30px;"></div>
  
  <h2 style="font-size: 1.2rem; margin-bottom: 15px; color: var(--muted);">Account Information</h2>
  
  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
    <div class="kpi">
        <div class="label">Full Name</div>
        <div class="value" id="profile_name" style="font-size: 1.1rem;">Loading...</div>
    </div>
    <div class="kpi">
        <div class="label">Email Address</div>
        <div class="value" id="profile_email" style="font-size: 1.1rem;">Loading...</div>
    </div>
    <div class="kpi">
        <div class="label">System Role</div>
        <div class="value" id="profile_role" style="font-size: 1.1rem; color: var(--accent); text-transform: capitalize;">Loading...</div>
    </div>
    <div class="kpi">
        <div class="label">Member Since</div>
        <div class="value" id="profile_date" style="font-size: 1.1rem;">Loading...</div>
    </div>
  </div>
</div>

<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
<script>
  (async () => {
    try {
      const data = await window.WM.getSession();
      if (data.ok && data.user) {
          const u = data.user;
          document.getElementById('profile_name').textContent = u.name;
          document.getElementById('profile_email').textContent = u.email;
          document.getElementById('profile_role').textContent = u.role;
          document.getElementById('profile_date').textContent = new Date(u.created_at).toLocaleDateString();
      }
    } catch (err) {
      console.error('Failed to load profile data', err);
    }
  })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>