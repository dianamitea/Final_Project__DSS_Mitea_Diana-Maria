<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT *, CONCAT(first_name,' ',last_name) AS full_name FROM customers WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$cust = $stmt->get_result()->fetch_assoc();
if (!$cust) {
    setFlash('danger', 'Customer not found.');
    header("Location: $adminBase/customers/index.php");
    exit;
}

$adminPageTitle = $cust['full_name'];

// Orders for this customer (match by email)
$oStmt = $conn->prepare(
    "SELECT id, order_code, total_price, status, delivery_date, created_at
     FROM orders WHERE customer_email = ? ORDER BY created_at DESC"
);
$oStmt->bind_param('s', $cust['email']);
$oStmt->execute();
$orders = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalSpent = array_sum(array_column($orders, 'total_price'));

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-user me-2 text-primary-custom"></i><?= htmlspecialchars($cust['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
  <a href="<?= $adminBase ?>/customers/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-form-card">
      <div class="form-section-title">Contact Information</div>
      <div class="mb-2">
        <small class="text-muted d-block">Email</small>
        <a href="mailto:<?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>
      <?php if ($cust['phone']): ?>
      <div class="mb-2">
        <small class="text-muted d-block">Phone</small>
        <strong><?= htmlspecialchars($cust['phone'], ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <?php endif; ?>
      <?php if ($cust['address']): ?>
      <div class="mb-2">
        <small class="text-muted d-block">Address</small>
        <?= nl2br(htmlspecialchars($cust['address'], ENT_QUOTES, 'UTF-8')) ?>
      </div>
      <?php endif; ?>
      <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between">
          <span class="text-muted">Total Orders</span>
          <strong><?= count($orders) ?></strong>
        </div>
        <div class="d-flex justify-content-between mt-1">
          <span class="text-muted">Total Spent</span>
          <strong class="text-primary-custom"><?= number_format($totalSpent, 2) ?> RON</strong>
        </div>
        <div class="d-flex justify-content-between mt-1">
          <span class="text-muted">Joined</span>
          <span><?= htmlspecialchars(date('d M Y', strtotime($cust['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-table">
      <div class="px-4 py-3 border-bottom fw-bold">Order History</div>
      <?php if (empty($orders)): ?>
        <div class="text-center py-5 text-muted">No orders yet.</div>
      <?php else: ?>
      <table class="table table-hover mb-0">
        <thead><tr><th>Code</th><th>Total</th><th>Status</th><th>Delivery</th><th>Placed</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td class="fw-semibold text-primary-custom">
              <?= htmlspecialchars($o['order_code'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td><?= number_format((float)$o['total_price'], 2) ?> RON</td>
            <td>
              <span class="status-badge status-<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(ucfirst(str_replace('_',' ',$o['status'])), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </td>
            <td class="small"><?= htmlspecialchars(date('d M Y', strtotime($o['delivery_date'])), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small text-muted"><?= htmlspecialchars(date('d M Y', strtotime($o['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <a href="<?= $adminBase ?>/orders/view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
