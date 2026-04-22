// main.js
// Small utilities shared by pages (no frameworks, no bundlers).

function withBaseUrl(url) {
  const base = String(window.BASE_URL || '/').replace(/\/?$/, '/');
  if (/^https?:\/\//i.test(url)) return url;
  const s = String(url);
  if (s.startsWith(base)) return s;
  return base + s.replace(/^\/+/, '');
}

async function getSession() {
  const res = await fetch(withBaseUrl('api/auth/session.php'), { credentials: 'same-origin' });
  return res.json();
}

async function apiLogout() {
  const res = await fetch(withBaseUrl('api/auth/logout.php'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({}),
  });
  return res.json();
}

window.WM = {
  getSession,
  apiLogout,
};

