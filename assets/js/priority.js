// priority.js — Priority badge helpers (vanilla JS).

(function () {
  const LABELS = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    critical: 'Critical',
  };

  function label(priority) {
    const key = String(priority || 'medium').toLowerCase();
    return LABELS[key] || key;
  }

  function createBadge(priority) {
    const key = String(priority || 'medium').toLowerCase();
    const span = document.createElement('span');
    span.className = 'priority-badge priority-' + key;
    span.textContent = label(key);
    return span;
  }

  window.Priority = { label, createBadge, LABELS };
})();
