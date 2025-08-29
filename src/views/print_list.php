<?php
/** @var array $list */
/** @var array $remaining */
/** @var array $done */
/** @var int   $doneCount */
/** @var int   $totalCount */
/** @var int   $percent */
/** @var float $sumDone */
/** @var float $sumTotal */
/** @var string $shareUrl */

$fmt = function (float $n): string {
    return number_format($n, 2, ',', ' ');
};
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="UTF-8">
  <title>Utskrift – <?= htmlspecialchars($list['title']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap för snygg layout även på skärm -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
	    :root {
      --bg-done: #e7f7ec;  /* ljusgrön för klara */
      --bg-remaining: #ffffff;
    }
    body { background: #f7f7f7; }
    .print-container { max-width: 920px; margin: 2rem auto; }
    .section-title { border-bottom: 1px solid #dee2e6; padding-bottom: .25rem; margin-top: 1.25rem; }
    .item-row { break-inside: avoid; }
    .done-row { background: var(--bg-done); }
    .remaining-row { background: var(--bg-remaining); }
    .muted { color:#6c757d; }

    /* Små badges i rubriken */
    .pill { font-size: .85rem; }

    /* Döljer toolbar, ram etc. vid utskrift */
    @media print {
      .no-print { display: none !important; }
      body { background: #fff; }
      .print-container { margin: 0; max-width: 100%; }
      a[href]:after { content: "" !important; } /* ta bort url-suffix i utskrift */
    }
  </style>
</head>
<body>
  <!-- Skärmtopprad (göms i print) -->
  <div class="no-print py-2 bg-white border-bottom">
    <div class="container d-flex flex-wrap gap-2 align-items-center">
      <strong class="me-auto">Utskriftsvy</strong>
      <div class="form-check me-2">
        <input class="form-check-input" type="checkbox" id="toggleRemaining" checked>
        <label class="form-check-label" for="toggleRemaining">Visa “Kvar”</label>
      </div>
      <div class="form-check me-2">
        <input class="form-check-input" type="checkbox" id="toggleDone" checked>
        <label class="form-check-label" for="toggleDone">Visa “Klart”</label>
      </div>
      <div class="form-check me-2">
        <input class="form-check-input" type="checkbox" id="togglePrices" checked>
        <label class="form-check-label" for="togglePrices">Visa priser & summering</label>
      </div>
      <div class="form-check me-2">
        <input class="form-check-input" type="checkbox" id="toggleQR">
        <label class="form-check-label" for="toggleQR">Visa QR-länk</label>
      </div>
      <button class="btn btn-primary" onclick="window.print()">Skriv ut / Spara som PDF</button>
    </div>
  </div>

  <div class="print-container bg-white shadow-sm p-4">
    <!-- Rubrik -->
    <div class="d-flex flex-wrap align-items-center justify-content-between">
      <h1 class="h4 mb-0"><?= htmlspecialchars($list['title']) ?></h1>
      <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-secondary pill">Klar: <?= $doneCount ?> av <?= $totalCount ?> (<?= $percent ?>%)</span>
        <?php if ($sumTotal > 0): ?>
          <span class="badge text-bg-success pill prices-pill">Summa klar: <?= $fmt($sumDone) ?> kr</span>
          <span class="badge text-bg-info pill prices-pill">Summa total: <?= $fmt($sumTotal) ?> kr</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="muted small mt-1">
      Utskriven: <?= date('Y-m-d H:i') ?> • Länk: <?= htmlspecialchars($shareUrl) ?>
    </div>

    <!-- QR (dold som standard, visning styrs av checkbox ovan) -->
    <div id="qrWrap" class="no-print mt-3 d-none">
      <img id="qrImg" alt="QR till listan" class="border p-1"
           src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($shareUrl) ?>">
    </div>

    <!-- Sektion: Kvar -->
    <h2 class="h6 section-title mt-4" id="remainingHeader">Kvar att köpa</h2>
    <div id="remainingSection" class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr class="small text-uppercase text-muted">
            <th style="width:55%">Produkt</th>
            <th style="width:20%">Mängd</th>
            <th style="width:15%" class="prices-pill">Pris (kr)</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($remaining) === 0): ?>
          <tr><td colspan="4" class="text-muted">Inga poster kvar.</td></tr>
        <?php else: foreach ($remaining as $r): ?>
          <tr class="item-row remaining-row">
            <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['quantity'] ?? '') ?></td>
            <td class="prices-pill">
              <?php
                $p = isset($r['cost']) && is_numeric($r['cost']) ? (float)$r['cost'] : 0.0;
                echo $p > 0 ? $fmt($p) : '';
              ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Sektion: Klart -->
    <h2 class="h6 section-title mt-4" id="doneHeader">Klart</h2>
    <div id="doneSection" class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr class="small text-uppercase text-muted">
            <th style="width:55%">Produkt</th>
            <th style="width:20%">Mängd</th>
            <th style="width:15%" class="prices-pill">Pris (kr)</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($done) === 0): ?>
          <tr><td colspan="4" class="text-muted">Inga poster klara.</td></tr>
        <?php else: foreach ($done as $d): ?>
          <tr class="item-row done-row">
            <td>
              <span class="me-1">✓</span>
              <span><?= htmlspecialchars($d['name'] ?? '') ?></span>
            </td>
            <td><?= htmlspecialchars($d['quantity'] ?? '') ?></td>
            <td class="prices-pill">
              <?php
                $p = isset($d['cost']) && is_numeric($d['cost']) ? (float)$d['cost'] : 0.0;
                echo $p > 0 ? $fmt($p) : '';
              ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="small text-muted mt-4">
      Tips: Använd “Spara som PDF” i utskriftsdialogen för att dela listan.
    </div>
  </div>

  <script>
    (function(){
      const el = (id)=>document.getElementById(id);

      const toggle = (chkId, sectionId, headerId) => {
        const on = el(chkId).checked;
        el(sectionId).classList.toggle('d-none', !on);
        el(headerId).classList.toggle('d-none', !on);
      };

      // Visa/dölj sektioner
      const applyToggles = () => {
        toggle('toggleRemaining','remainingSection','remainingHeader');
        toggle('toggleDone','doneSection','doneHeader');

        const pricesOn = el('togglePrices').checked;
        for (const n of document.querySelectorAll('.prices-pill')) {
          n.classList.toggle('d-none', !pricesOn);
        }

        const qrOn = el('toggleQR').checked;
        el('qrWrap').classList.toggle('d-none', !qrOn);
      };

      ['toggleRemaining','toggleDone','togglePrices','toggleQR'].forEach(id=>{
        const c = el(id);
        if (c) c.addEventListener('change', applyToggles);
      });
      applyToggles();
    })();
  </script>
</body>
</html>
