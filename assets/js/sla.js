// sla.js — SLA deadline badge helpers (vanilla JS).

(function () {
  function badgeClass(report) {
    if (report.is_resolved_late) {
      return 'sla-resolved-late';
    }
    if (report.is_overdue) {
      return 'sla-danger';
    }
    const hours = report.remaining_time && report.remaining_time.hours;
    if (typeof hours === 'number' && hours >= 0 && hours <= 24) {
      return 'sla-warning';
    }
    return 'sla-ok';
  }

  function label(report) {
    if (report.is_resolved_late) {
      return 'Resolved late';
    }
    if (report.remaining_time && report.remaining_time.human) {
      return report.remaining_time.human;
    }
    if (report.due_at) {
      return 'Due ' + report.due_at;
    }
    return 'No SLA';
  }

  function createBadge(report) {
    const span = document.createElement('span');
    span.className = 'sla-badge ' + badgeClass(report || {});
    span.textContent = label(report || {});
    return span;
  }

  window.SLA = {
    createBadge,
    badgeClass,
    label,
  };
})();
