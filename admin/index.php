<?php
/**
 * admin/index.php — Admin Dashboard (A3)
 * KPI cards, latest records, quick actions, low-stock alerts.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Dashboard';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// ── KPI queries ──
$totalOrders   = $conn->query("SELECT COUNT(*) AS n FROM orders")->fetch_assoc()['n'];
$pendingOrders = $conn->query("SELECT COUNT(*) AS n FROM orders WHERE status IN ('new','pending','confirmed','preparing')")->fetch_assoc()['n'];
$delivered     = $conn->query("SELECT COUNT(*) AS n FROM orders WHERE status = 'delivered'")->fetch_assoc()['n'];
$revenue       = $conn->query("SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['s'];
$totalCust     = $conn->query("SELECT COUNT(*) AS n FROM customers")->fetch_assoc()['n'];
$lowStockCount = $conn->query("SELECT COUNT(*) AS n FROM products WHERE stock_quantity <= 5 AND is_active = 1")->fetch_assoc()['n'];
$unpaidOrders  = $conn->query("SELECT COUNT(*) AS n FROM orders WHERE payment_status = 'unpaid' AND status NOT IN ('cancelled')")->fetch_assoc()['n'];

// ── Latest 5 orders ──
$latestOrders = $conn->query(
    "SELECT id, order_code, customer_name, total_price, status, created_at
     FROM orders ORDER BY created_at DESC LIMIT 5"
);

// ── Low-stock products (A5 efficiency: stock alert) ──
$lowStock = $conn->query(
    "SELECT p.name, p.stock_quantity, c.name AS cat
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE p.stock_quantity <= 5 AND p.is_active = 1
     ORDER BY p.stock_quantity ASC"
);

// ── This month revenue ──
$monthRevenue = $conn->query(
    "SELECT COALESCE(SUM(total_price),0) AS s FROM orders
     WHERE payment_status = 'paid'
     AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
)->fetch_assoc()['s'];

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-tachometer-alt me-2 text-primary-custom"></i>Dashboard</h1>
  <div class="d-flex gap-2">
    <a href="<?= $adminBase ?>/orders/create.php" class="btn btn-primary btn-sm">
      <i class="fa fa-plus me-1"></i>New Order
    </a>
    <a href="<?= $adminBase ?>/reports/index.php" class="btn btn-outline-primary btn-sm">
      <i class="fa fa-chart-bar me-1"></i>Reports
    </a>
  </div>
</div>

<!-- ── KPI Cards ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-pink"><i class="fa fa-shopping-bag"></i></div>
      <div>
        <div class="kpi-value text-primary-custom"><?= number_format($totalOrders) ?></div>
        <div class="kpi-label">Total Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-orange"><i class="fa fa-clock"></i></div>
      <div>
        <div class="kpi-value" style="color:#e65100"><?= number_format($pendingOrders) ?></div>
        <div class="kpi-label">Active Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-green"><i class="fa fa-check-circle"></i></div>
      <div>
        <div class="kpi-value text-success"><?= number_format($delivered) ?></div>
        <div class="kpi-label">Delivered</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-blue"><i class="fa fa-money-bill-wave"></i></div>
      <div>
        <div class="kpi-value text-primary"><?= number_format((float)$revenue, 0) ?></div>
        <div class="kpi-label">Revenue (RON)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-purple"><i class="fa fa-users"></i></div>
      <div>
        <div class="kpi-value" style="color:#4527a0"><?= number_format($totalCust) ?></div>
        <div class="kpi-label">Customers</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-pink"><i class="fa fa-calendar-alt"></i></div>
      <div>
        <div class="kpi-value text-primary-custom"><?= number_format((float)$monthRevenue, 0) ?></div>
        <div class="kpi-label">This Month (RON)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-orange"><i class="fa fa-exclamation-triangle"></i></div>
      <div>
        <div class="kpi-value" style="color:<?= $lowStockCount > 0 ? '#e65100' : '#2e7d32' ?>">
          <?= number_format($lowStockCount) ?>
        </div>
        <div class="kpi-label">Low Stock Items</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon kpi-orange"><i class="fa fa-file-invoice-dollar"></i></div>
      <div>
        <div class="kpi-value" style="color:#e65100"><?= number_format($unpaidOrders) ?></div>
        <div class="kpi-label">Unpaid Orders</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Latest orders -->
  <div class="col-lg-7">
    <div class="admin-table">
      <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
        <h6 class="fw-bold mb-0"><i class="fa fa-history me-2 text-primary-custom"></i>Latest Orders</h6>
        <a href="<?= $adminBase ?>/orders/index.php" class="btn btn-outline-primary btn-sm">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Code</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php while ($o = $latestOrders->fetch_assoc()): ?>
            <tr>
              <td class="fw-semibold text-primary-custom">
                <?= htmlspecialchars($o['order_code'], ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="fw-semibold"><?= number_format((float)$o['total_price'], 2) ?> RON</td>
              <td>
                <span class="status-badge status-<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars(ucfirst(str_replace('_',' ',$o['status'])), ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td class="text-muted small">
                <?= htmlspecialchars(date('d M', strtotime($o['created_at'])), ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td>
                <a href="<?= $adminBase ?>/orders/view.php?id=<?= (int)$o['id'] ?>"
                   class="btn btn-sm btn-outline-secondary">
                  <i class="fa fa-eye"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Low stock alerts (A5 efficiency) -->
  <div class="col-lg-5">
    <div class="admin-table">
      <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
        <h6 class="fw-bold mb-0">
          <i class="fa fa-exclamation-triangle me-2 text-warning"></i>Low Stock Alerts
        </h6>
        <a href="<?= $adminBase ?>/products/index.php" class="btn btn-outline-secondary btn-sm">Manage</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Product</th><th>Category</th><th>Stock</th></tr></thead>
          <tbody>
            <?php if ($lowStock->num_rows === 0): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">All products are well stocked!</td></tr>
            <?php else: ?>
            <?php while ($p = $lowStock->fetch_assoc()): ?>
            <tr>
              <td class="small"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="small text-muted"><?= htmlspecialchars($p['cat'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge <?= (int)$p['stock_quantity'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                  <?= (int)$p['stock_quantity'] ?> left
                </span>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="admin-table mt-4 p-4">
      <h6 class="fw-bold mb-3"><i class="fa fa-bolt me-2 text-primary-custom"></i>Quick Actions</h6>
      <div class="d-grid gap-2">
        <a href="<?= $adminBase ?>/orders/create.php" class="btn btn-primary btn-sm">
          <i class="fa fa-plus me-2"></i>Create New Order
        </a>
        <a href="<?= $adminBase ?>/products/create.php" class="btn btn-outline-primary btn-sm">
          <i class="fa fa-leaf me-2"></i>Add New Product
        </a>
        <a href="<?= $adminBase ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-chart-pie me-2"></i>View Reports
        </a>
        <a href="<?= $adminBase ?>/currency.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-exchange-alt me-2"></i>Check Exchange Rates
        </a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
