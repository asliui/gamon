// main.js — Shared session utilities.

async function getSession() {
  const url = window.WG ? window.WG.withBaseUrl('api/auth/session.php') : window.BASE_URL + 'api/auth/session.php';
  const res = await fetch(url, { credentials: 'same-origin' });
  return res.json();
}

async function apiLogout() {
  if (window.WG) {
    return window.WG.apiPost('api/auth/logout.php', {});
  }
  const token = String(window.CSRF_TOKEN || '');
  const res = await fetch(window.BASE_URL + 'api/auth/logout.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify({}),
  });
  return res.json();
}

window.WM = { getSession, apiLogout };
