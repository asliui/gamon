// auth.js
// Handles login/register forms using the JSON API.

async function apiPost(url, payload) {
  const fullUrl = withBaseUrl(url);
  const res = await fetch(fullUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = data?.error || `Request failed (${res.status})`;
    throw { status: res.status, data, message: err };
  }
  return data;
}

function withBaseUrl(url) {
  const base = String(window.BASE_URL || '/').replace(/\/?$/, '/');
  if (/^https?:\/\//i.test(url)) return url;
  const s = String(url);
  if (s.startsWith(base)) return s;
  const clean = s.replace(/^\/+/, '');
  return base + clean;
}

function showMsg(el, text, ok = false) {
  if (!el) return;
  el.style.display = 'block';
  el.classList.toggle('ok', !!ok);
  el.textContent = text;
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    msg && (msg.style.display = 'none');

    const payload = {
      email: loginForm.email.value,
      password: loginForm.password.value,
    };

    try {
      await apiPost('api/auth/login.php', payload);
      window.location.href = withBaseUrl('dashboard.php');
    } catch (err) {
      showMsg(msg, err.message || 'Login failed');
    }
  });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    msg && (msg.style.display = 'none');

    const payload = {
      name: registerForm.name.value,
      email: registerForm.email.value,
      role: registerForm.role.value,
      password: registerForm.password.value,
    };

    try {
      await apiPost('api/auth/register.php', payload);
      window.location.href = withBaseUrl('dashboard.php');
    } catch (err) {
      showMsg(msg, err.message || 'Registration failed');
    }
  });
}

