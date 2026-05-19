// report-timeline.js — Vertical activity timeline for report detail pages.

(function () {
  const LABELS = {
    report_created: 'Report created',
    report_updated: 'Report updated',
    report_deleted: 'Report deleted',
    report_priority_changed: 'Priority changed',
    report_status_changed: 'Status changed',
    report_assigned: 'Assigned to personnel',
    assignment_progress_changed: 'Work progress updated',
    category_created: 'Category created',
    category_updated: 'Category updated',
    category_deleted: 'Category deleted',
    area_created: 'Area created',
    area_updated: 'Area updated',
    area_deleted: 'Area deleted',
    user_role_changed: 'User role changed',
    user_soft_deleted: 'User account removed',
  };

  function actionLabel(action) {
    const key = String(action || '');
    return LABELS[key] || key.replace(/_/g, ' ');
  }

  function formatDetails(details) {
    if (!details) return '';
    try {
      const obj = typeof details === 'string' ? JSON.parse(details) : details;
      const parts = [];
      Object.keys(obj).forEach((k) => {
        parts.push(k + ': ' + String(obj[k]));
      });
      const text = parts.join(' · ');
      return text.length > 140 ? text.slice(0, 137) + '…' : text;
    } catch (e) {
      const text = String(details);
      return text.length > 140 ? text.slice(0, 137) + '…' : text;
    }
  }

  function formatTime(createdAt) {
    if (!createdAt) return '';
    const d = String(createdAt);
  if (d.length >= 16) {
      return d.slice(11, 16);
    }
    return d;
  }

  function render(container, items) {
    container.textContent = '';
    if (!items || !items.length) {
      const empty = document.createElement('p');
      empty.className = 'timeline-empty';
      empty.textContent = 'No timeline events yet.';
      container.appendChild(empty);
      return;
    }

    const list = document.createElement('div');
    list.className = 'timeline';

    items.forEach((item) => {
      const row = document.createElement('div');
      row.className = 'timeline-item';

      const dot = document.createElement('span');
      dot.className = 'timeline-dot';
      dot.setAttribute('aria-hidden', 'true');

      const content = document.createElement('div');
      content.className = 'timeline-content';

      const head = document.createElement('div');
      head.className = 'timeline-head';

      const time = document.createElement('span');
      time.className = 'timeline-time';
      time.textContent = '[' + formatTime(item.created_at) + ']';

      const title = document.createElement('strong');
      title.textContent = actionLabel(item.action);

      head.append(time, document.createTextNode(' '), title);

      const actor = document.createElement('div');
      actor.className = 'timeline-actor';
      actor.textContent = item.actor_name || item.actor_email || 'System';

      content.append(head, actor);

      const detailText = formatDetails(item.details);
      if (detailText) {
        const det = document.createElement('div');
        det.className = 'timeline-details';
        det.textContent = detailText;
        content.appendChild(det);
      }

      row.append(dot, content);
      list.appendChild(row);
    });

    container.appendChild(list);
  }

  async function load(containerId, reportId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.textContent = 'Loading timeline…';
    try {
      const data = await window.WG.apiGet(
        'api/reports/timeline.php?report_id=' + encodeURIComponent(String(reportId))
      );
      if (!data.ok) {
        throw new Error(data.error || 'Failed to load timeline');
      }
      render(container, data.items || []);
    } catch (err) {
      container.textContent = '';
      const errEl = document.createElement('p');
      errEl.className = 'timeline-empty timeline-error';
      errEl.textContent = err.message || 'Failed to load timeline.';
      container.appendChild(errEl);
    }
  }

  window.ReportTimeline = {
    load,
    actionLabel,
    formatDetails,
  };
})();
