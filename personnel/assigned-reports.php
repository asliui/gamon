<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$user = \WebGamon\Core\Auth::user();
if (!$user) {
    redirect(base_url('login.php'));
}
if ($user['role'] !== 'personnel') {
    redirect(base_url('dashboard.php'));
}

$title = 'My Assigned Tasks';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h1>Atanan Görevlerim</h1>
  <p>Size atanmış raporlar. Genel rapor durumu ile çalışma ilerlemenizi (assignment progress) ayrı güncelleyebilirsiniz.</p>
  <div class="spacer"></div>

  <div style="overflow-x: auto;">
    <table class="data-table" id="assignedTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kategori / Bölge</th>
          <th>Açıklama</th>
          <th>Rapor Durumu</th>
          <th>Çalışma Durumu</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody id="assignedBody">
        <tr><td colspan="6">Yükleniyor...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  const PROGRESS_LABELS = {
    not_started: 'Yapılmadı',
    in_progress: 'Yapılıyor',
    completed: 'Yapıldı',
  };

  function notify(msg, ok) {
    if (window.Toast) {
      ok ? window.Toast.success(msg) : window.Toast.error(msg);
    } else {
      alert(msg);
    }
  }

  async function updateReportStatus(reportId, newStatus) {
    try {
      await window.WG.apiPost('api/reports/update-status.php', {
        report_id: reportId,
        status: newStatus,
      });
      notify('Rapor durumu güncellendi.', true);
      loadAssigned();
    } catch (err) {
      notify(err.message || 'Durum güncellenemedi.', false);
    }
  }

  async function updateAssignmentProgress(reportId, progressStatus, progressNote, btn) {
    if (btn) btn.classList.add('btn-loading');
    try {
      await window.WG.apiPost('api/reports/update-assignment-progress.php', {
        report_id: reportId,
        progress_status: progressStatus,
        progress_note: progressNote,
      });
      notify('Çalışma durumu güncellendi.', true);
      loadAssigned();
    } catch (err) {
      notify(err.message || 'İlerleme güncellenemedi.', false);
    } finally {
      if (btn) btn.classList.remove('btn-loading');
    }
  }

  function buildProgressForm(item) {
    const wrap = document.createElement('div');
    wrap.className = 'progress-form';
    wrap.style.marginTop = '8px';
    wrap.style.padding = '10px';
    wrap.style.border = '1px solid var(--border)';
    wrap.style.borderRadius = '10px';
    wrap.style.background = 'rgba(0,0,0,0.15)';

    const selLabel = document.createElement('label');
    selLabel.textContent = 'Çalışma durumu';
    selLabel.style.display = 'block';
    selLabel.style.fontSize = '12px';
    selLabel.style.color = 'var(--muted)';
    selLabel.style.marginBottom = '4px';

    const sel = document.createElement('select');
    sel.style.width = '100%';
    sel.style.marginBottom = '8px';
    ['not_started', 'in_progress', 'completed'].forEach((val) => {
      const opt = document.createElement('option');
      opt.value = val;
      opt.textContent = PROGRESS_LABELS[val];
      if ((item.assignment_progress_status || 'not_started') === val) {
        opt.selected = true;
      }
      sel.appendChild(opt);
    });

    const noteLabel = document.createElement('label');
    noteLabel.textContent = 'Açıklama / not';
    noteLabel.style.display = 'block';
    noteLabel.style.fontSize = '12px';
    noteLabel.style.color = 'var(--muted)';
    noteLabel.style.marginBottom = '4px';

    const note = document.createElement('textarea');
    note.rows = 2;
    note.style.width = '100%';
    note.style.marginBottom = '8px';
    note.placeholder = 'Örn. Ekip olay yerine ulaştı...';
    note.value = item.assignment_progress_note || '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm';
    btn.style.background = 'var(--accent)';
    btn.textContent = 'Progress Güncelle';
    btn.addEventListener('click', () => {
      updateAssignmentProgress(item.id, sel.value, note.value.trim(), btn);
    });

    wrap.append(selLabel, sel, noteLabel, note, btn);
    return wrap;
  }

  async function loadAssigned() {
    const tbody = document.getElementById('assignedBody');
    tbody.innerHTML = '<tr><td colspan="6">Yükleniyor...</td></tr>';
    try {
      const data = await window.WG.apiGet('api/reports/list.php?assigned_to=me&limit=100');
      if (!data.items || data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="muted-cell">Atanmış görev yok. Açık raporlardan görev alın.</td></tr>';
        return;
      }

      tbody.textContent = '';
      data.items.forEach((item) => {
        const ps = item.assignment_progress_status || 'not_started';
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';

        const tdId = document.createElement('td');
        tdId.textContent = '#' + item.id;

        const tdCat = document.createElement('td');
        tdCat.appendChild(document.createTextNode(item.category || ''));
        tdCat.appendChild(document.createElement('br'));
        const small = document.createElement('small');
        small.style.color = 'var(--muted)';
        small.textContent = item.area || '';
        tdCat.appendChild(small);

        const desc = String(item.description || '');
        const tdDesc = document.createElement('td');
        tdDesc.textContent = desc.length > 60 ? desc.substring(0, 60) + '...' : desc;
        tdDesc.title = desc;

        const tdStatus = document.createElement('td');
        tdStatus.style.fontWeight = 'bold';
        tdStatus.textContent = String(item.status || '').toUpperCase();

        const tdProgress = document.createElement('td');
        const progMain = document.createElement('div');
        progMain.style.fontWeight = 'bold';
        progMain.textContent = PROGRESS_LABELS[ps] || ps;
        tdProgress.appendChild(progMain);
        if (item.assignment_progress_updated_at) {
          const upd = document.createElement('small');
          upd.style.display = 'block';
          upd.style.color = 'var(--muted)';
          upd.textContent = 'Son: ' + item.assignment_progress_updated_at;
          tdProgress.appendChild(upd);
        }
        if (item.assignment_progress_note) {
          const nt = document.createElement('small');
          nt.style.display = 'block';
          nt.style.marginTop = '4px';
          nt.textContent = item.assignment_progress_note;
          tdProgress.appendChild(nt);
        }

        const tdAct = document.createElement('td');
        tdAct.style.verticalAlign = 'top';

        const viewLink = document.createElement('a');
        viewLink.href = window.BASE_URL + 'personnel/report-detail.php?id=' + encodeURIComponent(String(item.id));
        viewLink.className = 'btn btn-sm';
        viewLink.textContent = 'Detay';
        viewLink.style.marginBottom = '8px';
        viewLink.style.display = 'inline-block';
        tdAct.appendChild(viewLink);

        if (item.status === 'assigned') {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'btn btn-sm';
          b.textContent = 'İşe Başla (rapor)';
          b.style.marginLeft = '6px';
          b.addEventListener('click', () => updateReportStatus(item.id, 'in_progress'));
          tdAct.appendChild(b);
        } else if (item.status === 'in_progress') {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'btn btn-sm';
          b.textContent = 'Çözüldü (rapor)';
          b.style.marginLeft = '6px';
          b.addEventListener('click', () => updateReportStatus(item.id, 'resolved'));
          tdAct.appendChild(b);
        }

        tdAct.appendChild(buildProgressForm(item));

        tr.append(tdId, tdCat, tdDesc, tdStatus, tdProgress, tdAct);
        tbody.appendChild(tr);
      });
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" class="danger-cell">Veri yüklenemedi.</td></tr>';
    }
  }

  loadAssigned();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
