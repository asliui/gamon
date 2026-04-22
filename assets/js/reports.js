// reports.js
// Minimal functions to create/list reports via JSON API.

function withBaseUrl(url) {
  const base = String(window.BASE_URL || '/').replace(/\/?$/, '/');
  if (/^https?:\/\//i.test(url)) return url;
  const s = String(url);
  if (s.startsWith(base)) return s;
  return base + s.replace(/^\/+/, '');
}

async function apiGet(url) {
  const res = await fetch(withBaseUrl(url), { credentials: 'same-origin' });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Request failed (${res.status})`);
  return data;
}

async function apiPost(url, payload) {
  const res = await fetch(withBaseUrl(url), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Request failed (${res.status})`);
  return data;
}

window.Reports = { apiGet, apiPost };

