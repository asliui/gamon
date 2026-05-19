// analytics-charts.js — CSS horizontal bar charts for admin analytics (vanilla JS).

(function () {
  const STATUS_LABELS = {
    open: 'Open',
    assigned: 'Assigned',
    in_progress: 'In Progress',
    resolved: 'Resolved',
  };

  const PRIORITY_LABELS = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    critical: 'Critical',
  };

  function formatLabel(key, map) {
    const k = String(key || '').toLowerCase();
    return map[k] || String(key || '');
  }

  function notify(msg, ok) {
    if (window.Toast) {
      ok ? window.Toast.success(msg) : window.Toast.error(msg);
    }
  }

  function sumCounts(items) {
    return (items || []).reduce((acc, row) => acc + (Number(row.count) || 0), 0);
  }

  function createBarRow(displayLabel, count, maxCount, extraClass) {
    const pct = maxCount > 0 ? Math.round((count / maxCount) * 100) : 0;

    const row = document.createElement('div');
    row.className = 'bar-row';

    const label = document.createElement('span');
    label.className = 'bar-label';
    label.textContent = displayLabel;

    const trackWrap = document.createElement('div');
    trackWrap.className = 'bar-track-wrap';

    const track = document.createElement('div');
    track.className = 'bar-track';
    track.setAttribute('role', 'presentation');

    const fill = document.createElement('div');
    fill.className = 'bar-fill' + (extraClass ? ' ' + extraClass : '');
    fill.style.width = pct + '%';

    const value = document.createElement('span');
    value.className = 'bar-value';
    value.textContent = String(count) + (maxCount > 0 ? ' (' + pct + '%)' : '');

    track.appendChild(fill);
    trackWrap.appendChild(track);
    row.append(label, trackWrap, value);
    return row;
  }

  function renderChartCard(container, title, items, labelMap, barClassPrefix) {
    const card = document.createElement('article');
    card.className = 'chart-card';

    const heading = document.createElement('h3');
    heading.className = 'chart-title';
    heading.textContent = title;
    card.appendChild(heading);

    const body = document.createElement('div');
    body.className = 'chart-body';

    const total = sumCounts(items);
    if (!items || items.length === 0 || total === 0) {
      const empty = document.createElement('p');
      empty.className = 'chart-empty';
      empty.textContent = 'No data available';
      body.appendChild(empty);
      card.appendChild(body);
      container.appendChild(card);
      return;
    }

    const maxCount = Math.max(...items.map((r) => Number(r.count) || 0), 1);

    items.forEach((item) => {
      const key = String(item.label || '');
      const count = Number(item.count) || 0;
      if (count <= 0 && labelMap) {
        return;
      }
      const display = labelMap ? formatLabel(key, labelMap) : key;
      const extra = barClassPrefix ? barClassPrefix + '-' + key.replace(/_/g, '-') : '';
      body.appendChild(createBarRow(display, count, maxCount, extra));
    });

    if (!body.childElementCount) {
      const empty = document.createElement('p');
      empty.className = 'chart-empty';
      empty.textContent = 'No data available';
      body.appendChild(empty);
    }

    card.appendChild(body);
    container.appendChild(card);
  }

  function setLoading(container) {
    container.textContent = '';
    const loading = document.createElement('p');
    loading.className = 'chart-empty';
    loading.textContent = 'Loading charts…';
    container.appendChild(loading);
  }

  function setError(container, message) {
    container.textContent = '';
    const err = document.createElement('p');
    err.className = 'chart-empty chart-error';
    err.textContent = message || 'Failed to load charts.';
    container.appendChild(err);
  }

  async function loadCharts(gridId) {
    const grid = document.getElementById(gridId || 'analyticsChartsGrid');
    if (!grid) return;

    setLoading(grid);

    try {
      const data = await window.WG.apiGet('api/analytics/distribution.php');
      if (!data.ok) {
        throw new Error(data.error || 'Distribution request failed');
      }

      grid.textContent = '';

      renderChartCard(grid, 'Status Distribution', data.status || [], STATUS_LABELS, 'bar-fill--status');
      renderChartCard(grid, 'Priority Distribution', data.priority || [], PRIORITY_LABELS, 'bar-fill--priority');
      renderChartCard(grid, 'Category Distribution', data.categories || [], null, 'bar-fill--category');
      renderChartCard(grid, 'Area Distribution', data.areas || [], null, 'bar-fill--area');
    } catch (err) {
      setError(grid, err.message || 'Failed to load charts.');
      notify(err.message || 'Failed to load analytics charts.', false);
    }
  }

  window.AnalyticsCharts = {
    load: loadCharts,
    STATUS_LABELS,
    PRIORITY_LABELS,
  };
})();
