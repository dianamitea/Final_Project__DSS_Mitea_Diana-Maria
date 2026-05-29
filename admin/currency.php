<?php
/**
 * admin/currency.php — Currency Rates page (A10)
 * External API call, JSON decoded, AJAX refresh, save rate to MySQL
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Currency Rates';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// Handle "save rate" POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $currency = strtoupper(preg_replace('/[^A-Z]/', '', $_POST['currency'] ?? ''));
    $rate     = (float)($_POST['rate'] ?? 0);
    if (strlen($currency) === 3 && $rate > 0) {
        $key  = 'saved_rate_' . $currency;
        $val  = json_encode(['currency' => $currency, 'rate' => $rate, 'saved_at' => date('Y-m-d H:i:s')]);
        $conn->execute_query(
            "INSERT INTO api_cache (cache_key, response_data, cached_at) VALUES (?,?, NOW())
             ON DUPLICATE KEY UPDATE response_data=VALUES(response_data), cached_at=NOW()",
            [$key, $val]
        );
        setFlash('success', "Rate 1 $currency = " . number_format(1 / $rate, 4) . " RON saved.");
    }
    header("Location: $adminBase/currency.php");
    exit;
}

// Load saved rates
$savedRates = [];
$savedStmt  = $conn->query("SELECT cache_key, response_data FROM api_cache WHERE cache_key LIKE 'saved_rate_%'");
while ($row = $savedStmt->fetch_assoc()) {
    $d = json_decode($row['response_data'], true);
    if ($d) $savedRates[$d['currency']] = $d;
}

// Check cached exchange data
$cached = $conn->query(
    "SELECT response_data, cached_at FROM api_cache WHERE cache_key = 'exchange_rates_RON' LIMIT 1"
)->fetch_assoc();

$displayCurrencies = ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD', 'SEK', 'NOK', 'DKK', 'HUF', 'BGN'];

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-exchange-alt me-2 text-primary-custom"></i>Currency Rates</h1>
  <button class="btn btn-primary" id="refreshRatesBtn">
    <i class="fa fa-sync me-1"></i>Refresh Rates
  </button>
</div>

<p class="text-muted mb-4">
  Live exchange rates relative to <strong>RON (Romanian Leu)</strong> — powered by
  <a href="https://www.exchangerate-api.com" target="_blank" rel="noopener">open.er-api.com</a>.
  Rates are cached for 1 hour.
</p>

<!-- Rates table -->
<div class="row g-4">
  <div class="col-lg-8">
    <div class="admin-table">
      <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
        <h6 class="fw-bold mb-0">Exchange Rates (Base: RON)</h6>
        <small class="text-muted" id="fetchedAt">
          <?= $cached ? 'Cached: ' . htmlspecialchars(date('d M Y H:i', strtotime($cached['cached_at'])), ENT_QUOTES, 'UTF-8') : 'Not loaded yet' ?>
        </small>
      </div>
      <div id="ratesTableContainer">
        <?php if ($cached): ?>
          <?php
          $cachedData = json_decode($cached['response_data'], true);
          $rates      = $cachedData['conversion_rates'] ?? $cachedData['rates'] ?? [];
          ?>
          <table class="table table-hover mb-0">
            <thead><tr><th>Currency</th><th>Code</th><th class="text-end">1 RON =</th><th class="text-end">1 unit = RON</th><th class="text-center">Save</th></tr></thead>
            <tbody>
              <?php foreach ($displayCurrencies as $code): ?>
              <?php if (!isset($rates[$code])) continue; ?>
              <tr>
                <td><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="text-end fw-semibold"><?= number_format((float)$rates[$code], 6) ?></td>
                <td class="text-end text-muted small"><?= number_format(1 / (float)$rates[$code], 4) ?> RON</td>
                <td class="text-center">
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="currency" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="rate" value="<?= htmlspecialchars($rates[$code], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-xs btn-outline-primary" style="font-size:.75rem;padding:2px 8px">Save</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="text-center py-5">
            <p class="text-muted">No rates loaded. Click <strong>Refresh Rates</strong> to fetch live data.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Saved rates -->
  <div class="col-lg-4">
    <div class="admin-table">
      <div class="px-4 py-3 border-bottom fw-bold">
        <h6 class="mb-0"><i class="fa fa-bookmark me-2 text-primary-custom"></i>Saved Rates</h6>
      </div>
      <?php if (empty($savedRates)): ?>
        <div class="text-center text-muted py-5 small">No rates saved yet.<br>Use the Save buttons to bookmark rates.</div>
      <?php else: ?>
        <table class="table table-hover mb-0">
          <thead><tr><th>Currency</th><th class="text-end">Rate</th><th class="text-muted small">Saved</th></tr></thead>
          <tbody>
            <?php foreach ($savedRates as $sr): ?>
            <tr>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sr['currency'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="text-end fw-semibold"><?= number_format(1/(float)$sr['rate'], 4) ?> RON</td>
              <td class="small text-muted"><?= htmlspecialchars(date('d M', strtotime($sr['saved_at'])), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="ratesError" class="alert alert-danger mt-3" style="display:none"></div>

<script>
const displayCurrencies = <?= json_encode($displayCurrencies) ?>;
const csrfToken         = '<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>';
const adminBase         = '<?= $adminBase ?>';

$('#refreshRatesBtn').on('click', function () {
  const $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Loading…');
  $('#ratesError').hide();

  $.getJSON(adminBase + '/ajax/currency_lookup.php', function (res) {
    if (!res.success) {
      $('#ratesError').text('Error: ' + res.message).show();
      return;
    }
    const rates = res.rates;
    let html = '<table class="table table-hover mb-0">';
    html += '<thead><tr><th>Currency</th><th>Code</th><th class="text-end">1 RON =</th><th class="text-end">1 unit = RON</th><th class="text-center">Save</th></tr></thead><tbody>';

    displayCurrencies.forEach(code => {
      if (!rates[code]) return;
      const rate    = rates[code];
      const inverse = (1 / rate).toFixed(4);
      html += `<tr>
        <td>${code}</td>
        <td><span class="badge bg-light text-dark border">${code}</span></td>
        <td class="text-end fw-semibold">${rate.toFixed(6)}</td>
        <td class="text-end text-muted small">${inverse} RON</td>
        <td class="text-center">
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="currency" value="${code}">
            <input type="hidden" name="rate" value="${rate}">
            <button type="submit" class="btn btn-xs btn-outline-primary" style="font-size:.75rem;padding:2px 8px">Save</button>
          </form>
        </td>
      </tr>`;
    });
    html += '</tbody></table>';
    $('#ratesTableContainer').html(html);
    $('#fetchedAt').text('Live: ' + res.fetched_at + ' (source: ' + res.source + ')');
  }).fail(function () {
    $('#ratesError').text('Request failed. Check your connection.').show();
  }).always(function () {
    $btn.prop('disabled', false).html('<i class="fa fa-sync me-1"></i>Refresh Rates');
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
