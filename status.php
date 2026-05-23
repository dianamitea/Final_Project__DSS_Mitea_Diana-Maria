<?php
/**
 * status.php — Order status lookup by code (P2 GET feature, P6)
 * GET ?code=ORD-XXXX
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Track Your Order';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

$order   = null;
$items   = null;
$history = null;
$notFound = false;
$searchCode = '';

// Attempt lookup
if (!empty($_GET['code'])) {
    $searchCode = preg_replace('/[^A-Z0-9\-]/', '', strtoupper(trim($_GET['code'])));
    if ($searchCode !== '') {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
        $stmt->bind_param('s', $searchCode);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if ($order) {
            $stmt2 = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
            $stmt2->bind_param('i', $order['id']);
            $stmt2->execute();
            $items = $stmt2->get_result();

            $stmt3 = $conn->prepare("SELECT * FROM status_history WHERE order_id = ? ORDER BY changed_at ASC");
            $stmt3->bind_param('i', $order['id']);
            $stmt3->execute();
            $history = $stmt3->get_result();
        } else {
            $notFound = true;
        }
    }
}

// Error from redirect
if (!empty($_GET['error']) && $_GET['error'] === 'notfound') {
    $notFound = true;
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container">
    <h1 class="fw-bold text-primary-custom mb-1">Track Your Order</h1>
    <p class="text-muted mb-0">Enter your order code to see the current status of your delivery.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <!-- Search form -->
    <div class="row justify-content-center mb-5">
      <div class="col-md-6">
        <div class="card p-4 shadow-sm">
          <label class="form-label fw-semibold">Your Order Code</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-search text-primary-custom"></i></span>
            <input type="text" id="statusSearchInput" class="form-control"
                   placeholder="e.g. ORD-0001"
                   value="<?= htmlspecialchars($searchCode, ENT_QUOTES, 'UTF-8') ?>">
            <button id="statusSearchBtn" class="btn btn-primary"
                    onclick="window.location.href='?code='+encodeURIComponent(document.getElementById('statusSearchInput').value)">
              Search
            </button>
          </div>
          <?php if ($notFound): ?>
            <div class="alert alert-warning mt-3 mb-0 small">
              <i class="fa fa-exclamation-triangle me-2"></i>
              Order code not found. Please check and try again.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($order): ?>
    <div class="confirmation-card">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
          <p class="text-muted small mb-1">ORDER CODE</p>
          <h3 class="fw-bold text-primary-custom mb-0">
            <?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?>
          </h3>
          <p class="text-muted small mt-1">Placed <?= htmlspecialchars(date('d M Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
          <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?> fs-6">
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status'])), ENT_QUOTES, 'UTF-8') ?>
          </span>
          <p class="small text-muted mt-1">
            Delivery: <?= htmlspecialchars(date('d M Y', strtotime($order['delivery_date'])), ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars(ucfirst($order['delivery_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </p>
        </div>
      </div>

      <hr>

      <!-- Items -->
      <h6 class="fw-semibold mb-2">Items Ordered</h6>
      <div class="table-responsive mb-4">
        <table class="table table-sm">
          <thead class="table-light">
            <tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr>
          </thead>
          <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= (int)$item['quantity'] ?></td>
              <td class="text-end"><?= number_format((float)$item['subtotal'], 2) ?> RON</td>
            </tr>
            <?php endwhile; ?>
          </tbody>
          <tfoot>
            <tr class="table-light">
              <th colspan="2" class="text-end">Total:</th>
              <th class="text-end text-primary-custom">
                <?= number_format((float)$order['total_price'], 2) ?> RON
              </th>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Status timeline -->
      <h6 class="fw-semibold mb-3">Status History</h6>
      <ul class="order-timeline">
        <?php while ($h = $history->fetch_assoc()): ?>
        <li>
          <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $h['new_status'])), ENT_QUOTES, 'UTF-8') ?></strong>
          <br>
          <small class="text-muted">
            <?= htmlspecialchars(date('d M Y, H:i', strtotime($h['changed_at'])), ENT_QUOTES, 'UTF-8') ?>
            <?php if ($h['notes']): ?>
              — <?= htmlspecialchars($h['notes'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
          </small>
        </li>
        <?php endwhile; ?>
      </ul>

      <div class="mt-4">
        <a href="<?= $base ?>/index.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-home me-2"></i>Back to Home
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
