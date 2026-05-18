// auth.js — Login/register via JSON API (uses api.js on public pages).

function withBaseUrl(url) {
  return window.WG ? window.WG.withBaseUrl(url) : (function () {
    const base = String(window.BASE_URL || '/').replace(/\/?$/, '/');
    return base + String(url).replace(/^\/+/, '');
  })();
}

function csrfToken() {
  if (window.WG) return window.WG.csrfToken();
  if (window.CSRF_TOKEN) return String(window.CSRF_TOKEN);
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? String(meta.getAttribute('content') || '') : '';
}

async function apiPost(url, payload) {
  const token = csrfToken();
  if (!token) {
    throw { message: 'Security token missing. Please refresh the page and try again.' };
  }
  const res = await fetch(withBaseUrl(url), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(payload || {}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw { message: data?.error || `Request failed (${res.status})` };
  }
  return data;
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
    try {
      await apiPost('api/auth/login.php', {
        email: loginForm.email.value,
        password: loginForm.password.value,
      });
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
    try {
      await apiPost('api/auth/register.php', {
        name: registerForm.name.value,
        email: registerForm.email.value,
        password: registerForm.password.value,
      });
      window.location.href = withBaseUrl('dashboard.php');
    } catch (err) {
      showMsg(msg, err.message || 'Registration failed');
    }
  });
}
