<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($list['title']) ?> – Inköpslista</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* ✓-markerade rader: ljusgrön bakgrund, genomstruken och något nedtonad */
    .done td{
      text-decoration: line-through;
      opacity: 0.6;
      background: var(--bs-success-bg-subtle, #d1e7dd);
    }
    .actions{ white-space:nowrap; }
    td[data-edit]{ cursor:pointer; }
    td[data-edit].editing{ padding:0.25rem; }
  </style>
</head>
<body class="container py-4">
  <div class="row">
    <div class="col-12 col-lg-10 mx-auto">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h5 m-0">Inköpslista: <?= htmlspecialchars($list['title']) ?></h1>

        <!-- Vi använder ett dolt fält enbart för att bära värdet om du vill läsa det i framtiden -->
        <input type="hidden" id="shareLink"
               value="<?= htmlspecialchars($baseUrl) ?>/?action=view&amp;id=<?= urlencode($list['id']) ?>">
        <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">
          📋 Kopiera länk
        </button>
        <a href="?action=print&id=<?= urlencode($list['id']) ?>" target="_blank"
        class="btn btn-outline-secondary ms-2">🖨️ Utskriftsvy</a>


      </div>
    </div>
   </div>
    <div class="card shadow-sm mb-3"><div class="card-body">
      <form class="row g-2" method="POST" action="?action=add_item">
        <input type="hidden" name="list_id" value="<?= htmlspecialchars($list['id']) ?>">
        <div class="col-md-5">
          <label class="form-label">Produkt</label>
          <input type="text" name="name" class="form-control" placeholder="Ex: Tomater" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Antal / vikt / volym</label>
          <input type="text" name="quantity" class="form-control" placeholder="Ex: 1 kg / 2 st">
        </div>
        <div class="col-md-2">
          <label class="form-label">Pris</label>
          <input type="number" name="cost" class="form-control" placeholder="t.ex. 19.90" step="0.01" min="0" inputmode="decimal">
        </div>
        <div class="col-md-2 d-grid">
          <label class="form-label d-none d-md-block">&nbsp;</label>
          <button class="btn btn-primary" type="submit">➕ Lägg till</button>
        </div>
      </form>
    </div></div>

    <div class="d-flex gap-2 mb-3">
      <button type="button" class="btn btn-outline-primary" onclick="copyLinkAndExit()">Kopiera länk &amp; avsluta</button>
    </div>

    <?php
      $total = count($items);
      $done = 0;
      $sumDone = 0.0;
      foreach ($items as $it) {
        if (!empty($it['checked'])) {
          $done++;
          if ($it['cost'] !== null && $it['cost'] !== '') $sumDone += (float)$it['cost'];
        }
      }
      $percent = $total ? (int) round($done * 100 / $total) : 0;
      $sumDoneFormatted = number_format($sumDone, 2, ',', ' ');
    ?>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div id="progress-text" class="fw-medium">
            Du är klar med <?= $done ?> av <?= $total ?> – <?= $percent ?>% av din lista
          </div>
          <div class="flex-grow-1 ms-lg-4" style="min-width:260px;">
            <div class="progress" role="progressbar" aria-label="Progress"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
              <div id="progress-bar" class="progress-bar" style="width: <?= $percent ?>%;"><?= $percent ?>%</div>
            </div>
          </div>
        </div>
        <div id="cost-summary" class="text-muted">
          Summa för klarmarkerat: <?= $sumDoneFormatted ?> kr
        </div>
      </div>
    </div>

    <div class="card shadow-sm"><div class="card-body p-0">
      <table class="table table-striped m-0 align-middle">
        <thead class="table-light"><tr>
          <th style="width:48px;"></th>
          <th>Produkt</th>
          <th style="width:220px;">Antal / vikt / volym</th>
          <th style="width:140px;">Pris</th>
          <th class="actions" style="width:80px;"></th>
        </tr></thead>
        <tbody id="items-tbody">
          <?php include \view('partials/items_rows'); ?>
        </tbody>
      </table>
    </div></div>

  </div></div>

  <!-- Toast för "länk kopierad" -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          Länk kopierad! Den finns i ditt urklipp — klistra in och dela. 📋
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Stäng"></button>
      </div>
    </div>
  </div>

  <!-- Bootstrap måste laddas före vårt script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  (() => {
    // ===== KONFIG/STATE =====
    const BASE_URL = <?= json_encode($baseUrl) ?>;
    const LIST_ID  = <?= json_encode($list['id']) ?>;
    const REFRESH_MS = 2000;

    let _editing = false;
    let _tabVisible = true;
    let _pendingCtrl = null;

    document.addEventListener('visibilitychange', () => { _tabVisible = !document.hidden; });

    // ===== TOAST =====
    function showCopyToast(){
      const el = document.getElementById('copyToast');
      if (!el) return;
      try {
        if (window.bootstrap && bootstrap.Toast){
          const t = bootstrap.Toast.getOrCreateInstance(el, { delay: 5000, autohide: true });
          t.show();
        } else {
          el.classList.add('show');
          setTimeout(()=>el.classList.remove('show'), 5000);
        }
      } catch (_) {}
    }

    // ===== LÄNKBYGGE & KOPIERING =====
    function getShareLink(){
      return `${BASE_URL}/?action=view&id=${encodeURIComponent(LIST_ID)}`;
    }

    function fallbackCopy(text){
      const tmp = document.createElement('input');
      tmp.value = text;
      document.body.appendChild(tmp);
      tmp.select(); tmp.setSelectionRange(0, 99999);
      document.execCommand('copy');
      tmp.remove();
    }

    function copyLink(){
      const link = getShareLink();
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(showCopyToast).catch(()=>{ fallbackCopy(link); showCopyToast(); });
      } else {
        fallbackCopy(link); showCopyToast();
      }
    }

    async function copyLinkAndExit(){
      const link = getShareLink();
      try {
        if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(link);
        else fallbackCopy(link);
      } catch (_) {}
      showCopyToast();
      setTimeout(()=>{ window.location.href='?'; }, 5200); // minst 5s
    }

    // Visa länken i input för användaren (om fältet finns)
    document.addEventListener('DOMContentLoaded', () => {
      const el = document.getElementById('shareLink');
      if (el) el.value = getShareLink();
    });

    // ===== INLINE-EDIT / AUTO-REFRESH / PROGRESS =====
    function attachEditable(){
      document.querySelectorAll('#items-tbody td[data-edit]').forEach(td=>{
        td.addEventListener('click', () => startEdit(td));
      });
    }

    function startEdit(td){
      if (_editing) return; _editing = true;
      td.classList.add('editing');
      const field = td.getAttribute('data-edit');
      const tr = td.closest('tr');
      const itemId = tr.getAttribute('data-id');
      const oldVal = td.textContent.trim().replace(/\s*kr\s*$/i,'');
      td.innerHTML = '';
      const input = document.createElement('input');
      input.type = (field === 'cost') ? 'number' : 'text';
      if (field === 'cost') { input.step = '0.01'; input.min = '0'; input.inputMode = 'decimal'; }
      input.className = 'form-control form-control-sm';
      input.value = oldVal;
      td.appendChild(input);
      input.focus(); input.select();

      const finish = (commit) => {
        const raw = input.value.trim();
        td.classList.remove('editing');

        if (!commit || raw === oldVal) {
          if (field === 'cost' && raw !== '') td.textContent = formatSEK(raw);
          else td.textContent = oldVal;
          _editing = false;
          return;
        }

        saveEdit(itemId, field, raw).then(ok=>{
          if (ok) {
            if (field === 'cost') {
              const num = normalizeNumber(raw);
              td.setAttribute('data-cost', (num ?? '').toString());
              td.textContent = (num == null ? '' : formatSEK(num));
            } else {
              td.textContent = raw;
            }
            updateProgressFromDOM();
          } else {
            if (field === 'cost' && oldVal !== '') td.textContent = formatSEK(oldVal);
            else td.textContent = oldVal;
          }
          _editing = false;
        });
      };

      input.addEventListener('keydown', e=>{
        if (e.key === 'Enter') { e.preventDefault(); finish(true); }
        if (e.key === 'Escape') { e.preventDefault(); finish(false); }
      });
      input.addEventListener('blur', () => finish(true));
    }

    function normalizeNumber(v){
      const s = String(v).replace(/[^\d,.\-]/g,'').replace(',', '.');
      const n = parseFloat(s);
      return Number.isFinite(n) ? n : null;
    }

    function formatSEK(v){
      const n = normalizeNumber(v);
      if (n == null) return '';
      return n.toLocaleString('sv-SE', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' kr';
    }

    async function saveEdit(itemId, field, value){
      try {
        const resp = await fetch('?action=update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ list_id: LIST_ID, item_id: itemId, field, value })
        });
        const data = await resp.json();
        return !!data.success;
      } catch (e) { return false; }
    }

    async function refreshRows(){
      if (_editing || !_tabVisible) return;
      try{
        if (_pendingCtrl) { _pendingCtrl.abort(); }
        _pendingCtrl = new AbortController();
        const res = await fetch(`?action=view&id=${encodeURIComponent(LIST_ID)}&partial=rows`, {
          cache: 'no-store', signal: _pendingCtrl.signal
        });
        if (!res.ok) return;
        const html = await res.text();
        const tbody = document.getElementById('items-tbody');
        if (tbody && tbody.innerHTML !== html) {
          tbody.innerHTML = html;
          attachEditable();
          updateProgressFromDOM();
        }
      }catch(e){ /* ignore */ }
      finally { _pendingCtrl = null; }
    }

    function updateProgressFromDOM(){
      const rows = Array.from(document.querySelectorAll('#items-tbody tr'));
      const boxes = rows.map(r => r.querySelector('input[type="checkbox"]')).filter(Boolean);
      const total = boxes.length;
      const done = boxes.filter(b => b.checked).length;
      const percent = total ? Math.round(done * 100 / total) : 0;

      const txt = document.getElementById('progress-text');
      const bar = document.getElementById('progress-bar');
      if (txt) txt.textContent = `Du är klar med ${done} av ${total} – ${percent}% av din lista`;
      if (bar) {
        bar.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', String(percent));
        bar.textContent = `${percent}%`;
      }

      // Summa för klarmarkerade
      let sum = 0;
      rows.forEach(r => {
        const cb = r.querySelector('input[type="checkbox"]');
        if (cb && cb.checked) {
          const c = r.querySelector('td[data-edit="cost"]');
          if (c) {
            const raw = c.getAttribute('data-cost') ?? c.textContent.trim();
            const num = normalizeNumber(raw);
            if (num != null) sum += num;
          }
        }
      });
      const el = document.getElementById('cost-summary');
      if (el) el.textContent = `Summa för klarmarkerat: ${sum.toLocaleString('sv-SE', {minimumFractionDigits:2, maximumFractionDigits:2})} kr`;
    }

    attachEditable();
    updateProgressFromDOM();
    setInterval(refreshRows, REFRESH_MS);

    // Exponera för onclick och konsol
    window.copyLink = copyLink;
    window.copyLinkAndExit = copyLinkAndExit;
    window.getShareLink = getShareLink;
  })();
  </script>
</body>
</html>
