// toast.js — Lightweight notifications (vanilla JS).

(function () {
  let container = null;

  function ensureContainer() {
    if (container) return container;
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
    return container;
  }

  function showToast(message, type) {
    const root = ensureContainer();
    const el = document.createElement('div');
    el.className = 'toast toast-' + (type || 'info');
    el.textContent = String(message ?? '');
    root.appendChild(el);
    requestAnimationFrame(() => el.classList.add('toast-show'));
    setTimeout(() => {
      el.classList.remove('toast-show');
      setTimeout(() => el.remove(), 300);
    }, 4000);
  }

  window.Toast = {
    success: (msg) => showToast(msg, 'success'),
    error: (msg) => showToast(msg, 'error'),
    info: (msg) => showToast(msg, 'info'),
  };
})();
