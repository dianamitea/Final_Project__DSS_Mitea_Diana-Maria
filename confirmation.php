<?php
/**
 * confirmation.php — Order confirmation page (P6)
 * GET ?code=ORD-XXXX — loads order from MySQL and shows details.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Order Confirmation';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

// Validate code param
$code = '';
if (!empty($_GET['code'])) {
    $code = preg_replace('/[^A-Z0-9\-]/', '', strtoupper(trim($_GET['code'])));
}
if ($code === '') {
    header('Location: ' . $base . '/index.php');
    exit;
}

// Fetch order from DB
$stmt = $conn->prepare(
    "SELECT * FROM orders WHERE order_code = ?"
);
$stmt->bind_param('s', $code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: ' . $base . '/status.php?error=notfound');
    exit;
}

// Fetch items
$stmt2 = $conn->prepare(
    "SELECT * FROM order_items WHERE order_id = ? ORDER BY id"
);
$stmt2->bind_param('i', $order['id']);
$stmt2->execute();
$items = $stmt2->get_result();

include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container text-center">
    <i class="fa fa-check-circle fa-3x text-success mb-2"></i>
    <h1 class="fw-bold text-primary-custom">Order Placed Successfully!</h1>
    <p class="text-muted">Thank you for your order. Keep your order code to track your delivery.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="confirmation-card">
      <!-- Order header -->
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
          <p class="text-muted small mb-1">ORDER CODE</p>
          <h3 class="fw-bold text-primary-custom mb-0">
            <?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?>
          </h3>
          <p class="text-muted small mt-1">Placed on <?= htmlspecialchars(date('d M Y, H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="text-end">
          <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?> fs-6">
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status'])), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      </div>

      <hr>

      <!-- Customer & delivery -->
      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <h6 class="fw-semibold mb-2">Customer Details</h6>
          <p class="mb-1 small"><i class="fa fa-user me-2 text-muted"></i><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="mb-1 small"><i class="fa fa-envelope me-2 text-muted"></i><?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="mb-0 small"><i class="fa fa-phone me-2 text-muted"></i><?= htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="col-md-6">
          <h6 class="fw-semibold mb-2">Delivery Details</h6>
          <p class="mb-1 small"><i class="fa fa-map-marker-alt me-2 text-muted"></i>
            <?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?>,
            <?= htmlspecialchars($order['delivery_city'], ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p class="mb-1 small"><i class="fa fa-calendar me-2 text-muted"></i>
            <?= htmlspecialchars(date('d M Y', strtotime($order['delivery_date'])), ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars(ucfirst($order['delivery_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p class="mb-0 small"><i class="fa fa-heart me-2 text-muted"></i>
            Occasion: <?= htmlspecialchars(ucfirst(str_replace('_', "'s ", $order['occasion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
          </p>
        </div>
      </div>

      <!-- Items table -->
      <h6 class="fw-semibold mb-2">Order Items</h6>
      <div class="table-responsive mb-3">
        <table class="table table-sm">
          <thead class="table-light">
            <tr>
              <th>Product</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= (int)$item['quantity'] ?></td>
              <td class="text-end"><?= number_format((float)$item['unit_price'], 2) ?> RON</td>
              <td class="text-end fw-semibold"><?= number_format((float)$item['subtotal'], 2) ?> RON</td>
            </tr>
            <?php endwhile; ?>
          </tbody>
          <tfoot>
            <tr class="table-light">
              <th colspan="3" class="text-end">Total:</th>
              <th class="text-end text-primary-custom fs-5">
                <?= number_format((float)$order['total_price'], 2) ?> RON
              </th>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php if ($order['card_message']): ?>
      <div class="alert alert-light border mb-3">
        <i class="fa fa-envelope-open-text me-2 text-primary-custom"></i>
        <strong>Card Message:</strong>
        <em>"<?= htmlspecialchars($order['card_message'], ENT_QUOTES, 'UTF-8') ?>"</em>
      </div>
      <?php endif; ?>

      <!-- Buttons -->
      <div class="d-flex gap-3 flex-wrap mt-4">
        <a href="<?= $base ?>/status.php?code=<?= urlencode($order['order_code']) ?>"
           class="btn btn-primary">
          <i class="fa fa-search me-2"></i>Track My Order
        </a>
        <a href="<?= $base ?>/index.php" class="btn btn-outline-secondary">
          <i class="fa fa-home me-2"></i>Back to Home
        </a>
        <a href="<?= $base ?>/products.php" class="btn btn-outline-primary">
          <i class="fa fa-leaf me-2"></i>Browse More Flowers
        </a>
      </div>

      <div class="alert alert-info mt-4 small mb-0">
        <i class="fa fa-info-circle me-2"></i>
        Save your order code <strong><?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></strong>
        to track the status of your order. You'll also receive a confirmation email.
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
