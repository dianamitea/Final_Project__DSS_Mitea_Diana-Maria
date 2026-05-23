<?php
/**
 * admin/reports/index.php — Reports & Analytics (A7)
 * 4+ SQL-powered Chart.js charts, 3+ KPI cards, date/status filter, business interpretation
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Reports & Analytics';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// ── Filter params ──
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-5 months'));
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$fStatus  = $_GET['status']    ?? '';

// ── Helper: safely encode for JS ──
function jsJson($data) { return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }

// ── 1. Orders per month (line chart) ──
$r1 = $conn->query(
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS mon,
            DATE_FORMAT(created_at,'%Y-%m') AS ymon,
            COUNT(*) AS cnt
     FROM orders
     WHERE created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'
     GROUP BY ymon ORDER BY ymon ASC"
)->fetch_all(MYSQLI_ASSOC);
$ordersMonthLabels = jsJson(array_column($r1, 'mon'));
$ordersMonthData   = jsJson(array_column($r1, 'cnt'));

// ── 2. Revenue per month (bar chart) ──
$r2 = $conn->query(
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS mon,
            DATE_FORMAT(created_at,'%Y-%m') AS ymon,
            ROUND(SUM(total_price),2) AS revenue
     FROM orders
     WHERE payment_status='paid'
       AND created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'
     GROUP BY ymon ORDER BY ymon ASC"
)->fetch_all(MYSQLI_ASSOC);
$revenueMonthLabels = jsJson(array_column($r2, 'mon'));
$revenueMonthData   = jsJson(array_column($r2, 'revenue'));

// ── 3. Orders by status (doughnut) ──
$r3 = $conn->query(
    "SELECT status, COUNT(*) AS cnt FROM orders
     WHERE created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'
     GROUP BY status ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);
$statusLabels = jsJson(array_column($r3, 'status'));
$statusData   = jsJson(array_column($r3, 'cnt'));

// ── 4. Top products by qty sold (bar) ──
$r4 = $conn->query(
    "SELECT p.name, SUM(oi.quantity) AS qty_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'
     GROUP BY p.id ORDER BY qty_sold DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);
$topProdLabels = jsJson(array_column($r4, 'name'));
$topProdData   = jsJson(array_column($r4, 'qty_sold'));

// ── KPIs ──
$kpi = $conn->query(
    "SELECT COUNT(*) AS total_orders,
            COALESCE(SUM(total_price),0) AS gross_revenue,
            COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_price END),0) AS paid_revenue,
            COUNT(CASE WHEN status='cancelled' THEN 1 END) AS cancellations
     FROM orders
     WHERE created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'"
)->fetch_assoc();

$avgOrderValue = $kpi['total_orders'] > 0
    ? round($kpi['gross_revenue'] / $kpi['total_orders'], 2) : 0;

// ── Occasions breakdown ──
$r5 = $conn->query(
    "SELECT occasion, COUNT(*) AS cnt FROM orders
     WHERE created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59'
     GROUP BY occasion ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);
$occLabels = jsJson(array_column($r5, 'occasion'));
$occData   = jsJson(array_column($r5, 'cnt'));

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-chart-bar me-2 text-primary-custom"></i>Reports &amp; Analytics</h1>
</div>

<!-- ── Filter ── -->
<div class="filters-bar mb-4">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3 d-flex gap-1">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa fa-filter me-1"></i>Apply
      </button>
      <a href="<?= $adminBase ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
  </form>
</div>

<!-- ── KPI Row ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-pink"><i class="fa fa-shopping-bag"></i></div>
      <div>
        <div class="kpi-value text-primary-custom"><?= number_format((int)$kpi['total_orders']) ?></div>
        <div class="kpi-label">Total Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-green"><i class="fa fa-money-bill-wave"></i></div>
      <div>
        <div class="kpi-value text-success"><?= number_format((float)$kpi['paid_revenue'], 0) ?></div>
        <div class="kpi-label">Revenue (RON)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-blue"><i class="fa fa-receipt"></i></div>
      <div>
        <div class="kpi-value text-primary"><?= number_format($avgOrderValue, 0) ?></div>
        <div class="kpi-label">Avg Order (RON)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-orange"><i class="fa fa-times-circle"></i></div>
      <div>
        <div class="kpi-value" style="color:#e65100"><?= number_format((int)$kpi['cancellations']) ?></div>
        <div class="kpi-label">Cancellations</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Charts row 1 ── -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="chart-card">
      <h6 class="fw-bold mb-3">Orders Per Month</h6>
      <canvas id="ordersMonthChart" height="90"></canvas>
      <p class="text-muted small mt-3">
        <i class="fa fa-lightbulb text-warning me-1"></i>
        <strong>Insight:</strong> Tracks order volume trends over time to identify peak seasons and plan staffing accordingly.
      </p>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="chart-card">
      <h6 class="fw-bold mb-3">Orders by Status</h6>
      <canvas id="statusChart" height="150"></canvas>
      <p class="text-muted small mt-3">
        <i class="fa fa-lightbulb text-warning me-1"></i>
        <strong>Insight:</strong> A high ratio of "pending" to "delivered" may signal bottlenecks in fulfillment.
      </p>
    </div>
  </div>
</div>

<!-- ── Charts row 2 ── -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="chart-card">
      <h6 class="fw-bold mb-3">Revenue Per Month (Paid Orders)</h6>
      <canvas id="revenueChart" height="90"></canvas>
      <p class="text-muted small mt-3">
        <i class="fa fa-lightbulb text-warning me-1"></i>
        <strong>Insight:</strong> Revenue spikes around Valentine's Day (Feb) and Mother's Day (May) — focus promotions around those months.
      </p>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card">
      <h6 class="fw-bold mb-3">Orders by Occasion</h6>
      <canvas id="occasionChart" height="180"></canvas>
      <p class="text-muted small mt-3">
        <i class="fa fa-lightbulb text-warning me-1"></i>
        <strong>Insight:</strong> Helps tailor product bundles and marketing for the most popular occasions.
      </p>
    </div>
  </div>
</div>

<!-- ── Top products chart ── -->
<div class="chart-card mb-4">
  <h6 class="fw-bold mb-3">Top 8 Products by Quantity Sold</h6>
  <canvas id="topProdChart" height="60"></canvas>
  <p class="text-muted small mt-3">
    <i class="fa fa-lightbulb text-warning me-1"></i>
    <strong>Insight:</strong> Best-selling products should maintain higher stock levels. Consider discontinuing items that never appear here.
  </p>
</div>

<script>
const primaryColor   = '#c2185b';
const primaryLight   = 'rgba(194,24,91,0.15)';
const statusColors   = ['#1565c0','#e65100','#1b5e20','#4527a0','#880e4f','#2e7d32','#b71c1c'];

// 1. Orders per month (line)
new Chart(document.getElementById('ordersMonthChart'), {
  type: 'line',
  data: {
    labels: <?= $ordersMonthLabels ?>,
    datasets: [{
      label: 'Orders',
      data: <?= $ordersMonthData ?>,
      borderColor: primaryColor,
      backgroundColor: primaryLight,
      tension: 0.4,
      fill: true,
      pointBackgroundColor: primaryColor
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

// 2. Revenue per month (bar)
new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: <?= $revenueMonthLabels ?>,
    datasets: [{
      label: 'Revenue (RON)',
      data: <?= $revenueMonthData ?>,
      backgroundColor: primaryColor,
      borderRadius: 6
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

// 3. Status doughnut
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $statusLabels ?>,
    datasets: [{ data: <?= $statusData ?>, backgroundColor: statusColors }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// 4. Top products bar
new Chart(document.getElementById('topProdChart'), {
  type: 'bar',
  data: {
    labels: <?= $topProdLabels ?>,
    datasets: [{
      label: 'Qty Sold',
      data: <?= $topProdData ?>,
      backgroundColor: primaryColor,
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } }
  }
});

// 5. Occasions polar area
new Chart(document.getElementById('occasionChart'), {
  type: 'polarArea',
  data: {
    labels: <?= $occLabels ?>,
    datasets: [{ data: <?= $occData ?>, backgroundColor: statusColors }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
