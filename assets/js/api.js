// api.js — Shared fetch helpers with CSRF (X-CSRF-Token header only).

function withBaseUrl(url) {
  const base = String(window.BASE_URL || '/').replace(/\/?$/, '/');
  if (/^https?:\/\//i.test(url)) return String(url);
  const s = String(url);
  if (s.startsWith('/')) return s;
  if (s.startsWith(base)) return s;
  return base + s.replace(/^\/+/, '');
}

function csrfToken() {
  if (window.CSRF_TOKEN) return String(window.CSRF_TOKEN);
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta && meta.getAttribute('content')) {
    return String(meta.getAttribute('content'));
  }
  const el = document.querySelector('[data-csrf-token]');
  if (el) return String(el.getAttribute('data-csrf-token') || '');
  return '';
}

function apiGet(url) {
  return fetch(withBaseUrl(url), { credentials: 'same-origin' }).then(async (res) => {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data?.error || `Request failed (${res.status})`);
    return data;
  });
}

function apiPost(url, payload) {
  const token = csrfToken();
  if (!token) {
    return Promise.reject(new Error('Security token missing. Please refresh the page.'));
  }
  return fetch(withBaseUrl(url), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify(payload || {}),
  }).then(async (res) => {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data?.error || `Request failed (${res.status})`);
    return data;
  });
}

window.WG = { withBaseUrl, csrfToken, apiGet, apiPost };
window.Reports = window.WG;
