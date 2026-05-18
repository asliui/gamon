// dom-safe.js — XSS-safe DOM helpers (no frameworks).

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function setAlert(el, message) {
  el.textContent = '';
  const div = document.createElement('div');
  div.className = 'alert';
  div.textContent = String(message ?? '');
  el.appendChild(div);
}

window.DomSafe = { escapeHtml, setAlert };
