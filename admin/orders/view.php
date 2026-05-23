<?php
/**
 * admin/orders/view.php — Order detail, status history, files (A4, A6, A8)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT * FROM orders WHERE id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    setFlash('danger', 'Order not found.');
    header("Location: $adminBase/orders/index.php");
    exit;
}

$adminPageTitle = 'Order ' . $order['order_code'];

// Items
$items = $conn->prepare(
    "SELECT oi.*, p.name AS product_name FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?"
);
$items->bind_param('i', $id);
$items->execute();
$items = $items->get_result()->fetch_all(MYSQLI_ASSOC);

// Status history
$history = $conn->prepare(
    "SELECT * FROM status_history WHERE order_id = ? ORDER BY changed_at ASC"
);
$history->bind_param('i', $id);
$history->execute();
$history = $history->get_result()->fetch_all(MYSQLI_ASSOC);

// Uploaded files
$files = $conn->prepare("SELECT * FROM uploaded_files WHERE order_id = ? ORDER BY uploaded_at DESC");
$files->bind_param('i', $id);
$files->execute();
$files = $files->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fa fa-file-alt me-2 text-primary-custom"></i><?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="text-muted mb-0">Placed on <?= htmlspecialchars(date('d M Y, H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= $adminBase ?>/orders/edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
      <i class="fa fa-edit me-1"></i>Edit
    </a>
    <a href="<?= $adminBase ?>/orders/print.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
      <i class="fa fa-print me-1"></i>Print
    </a>
    <a href="<?= $adminBase ?>/orders/pdf.php?id=<?= $id ?>" class="btn btn-outline-danger btn-sm" target="_blank">
      <i class="fa fa-file-pdf me-1"></i>PDF
    </a>
    <a href="<?= $adminBase ?>/orders/index.php" class="btn btn-outline-secondary btn-sm">
      <i class="fa fa-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Order details -->
  <div class="col-lg-8">
    <!-- Customer info -->
    <div class="admin-form-card mb-4">
      <div class="form-section-title">Customer Information</div>
      <div class="row g-3">
        <div class="col-md-6">
          <small class="text-muted d-block">Name</small>
          <strong><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Email</small>
          <a href="mailto:<?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Phone</small>
          <?= htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="col-md-6">
          <small class="text-muted d-block">Occasion</small>
          <?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['occasion']??'')), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="col-12">
          <small class="text-muted d-block">Delivery Address</small>
          <?= nl2br(htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8')) ?>
        </div>
        <?php if ($order['card_message']): ?>
        <div class="col-12">
          <small class="text-muted d-block">Card Message</small>
          <em><?= htmlspecialchars($order['card_message'], ENT_QUOTES, 'UTF-8') ?></em>
        </div>
        <?php endif; ?>
        <?php if ($order['special_requests']): ?>
        <div class="col-12">
          <small class="text-muted d-block">Special Requests</small>
          <?= nl2br(htmlspecialchars($order['special_requests'], ENT_QUOTES, 'UTF-8')) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order items -->
    <div class="admin-table mb-4">
      <div class="px-4 py-3 border-bottom fw-bold">Order Items</div>
      <table class="table table-hover mb-0">
        <thead><tr><th>Product</th><th class="text-end">Unit Price</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['product_name'] ?? 'Deleted product', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end"><?= number_format((float)$it['unit_price'], 2) ?> RON</td>
            <td class="text-center"><?= (int)$it['quantity'] ?></td>
            <td class="text-end fw-semibold"><?= number_format((float)$it['subtotal'], 2) ?> RON</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="table-light">
            <td colspan="3" class="text-end fw-bold">Total</td>
            <td class="text-end fw-bold text-primary-custom fs-5">
              <?= number_format((float)$order['total_price'], 2) ?> RON
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Uploaded files (A8) -->
    <?php if (!empty($files)): ?>
    <div class="admin-table mb-4">
      <div class="px-4 py-3 border-bottom fw-bold">Attached Files</div>
      <table class="table table-hover mb-0">
        <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($files as $f): ?>
          <tr>
            <td>
              <i class="fa fa-file me-2 text-muted"></i>
              <?= htmlspecialchars($f['original_name'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="small"><?= htmlspecialchars($f['file_type'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small"><?= round($f['file_size'] / 1024) ?> KB</td>
            <td class="small text-muted">
              <?= htmlspecialchars(date('d M Y', strtotime($f['uploaded_at'])), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td>
              <a href="/Final_Project__DSS_Mitea_Diana-Maria/<?= htmlspecialchars($f['file_path'], ENT_QUOTES, 'UTF-8') ?>"
                 class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="fa fa-download"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right: Status + Payment -->
  <div class="col-lg-4">
    <!-- Status card -->
    <div class="admin-form-card mb-4">
      <div class="form-section-title">Order Status</div>
      <div class="mb-3">
        <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?> fs-6">
          <?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['status'])), ENT_QUOTES, 'UTF-8') ?>
        </span>
      </div>
      <div class="mb-3">
        <small class="text-muted d-block">Delivery Date</small>
        <strong><?= htmlspecialchars(date('d M Y', strtotime($order['delivery_date'])), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if (strtotime($order['delivery_date']) < time() && !in_array($order['status'], ['delivered','cancelled'])): ?>
          <span class="badge bg-danger ms-2">Overdue</span>
        <?php endif; ?>
      </div>

      <!-- Quick status update -->
      <div class="form-section-title mt-3">Quick Update</div>
      <select id="quickStatus" class="form-select form-select-sm mb-2">
        <?php foreach (['new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_',' ',$s)) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="quickNote" class="form-control form-control-sm mb-2" placeholder="Optional note">
      <button type="button" id="updateStatusBtn" class="btn btn-primary btn-sm w-100">
        <i class="fa fa-save me-1"></i>Update Status
      </button>
      <div id="statusFeedback" class="mt-2 small"></div>
    </div>

    <!-- Payment -->
    <div class="admin-form-card mb-4">
      <div class="form-section-title">Payment</div>
      <div class="mb-2">
        <small class="text-muted d-block">Method</small>
        <strong><?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['payment_method'])), ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <div>
        <small class="text-muted d-block">Status</small>
        <span class="status-badge payment-<?= htmlspecialchars($order['payment_status'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars(ucfirst($order['payment_status']), ENT_QUOTES, 'UTF-8') ?>
        </span>
      </div>
    </div>

    <!-- Status Timeline (A6) -->
    <div class="admin-form-card">
      <div class="form-section-title">Status History</div>
      <div class="order-timeline">
        <?php foreach ($history as $h): ?>
        <div class="timeline-item d-flex gap-3 mb-3">
          <div class="flex-shrink-0">
            <span class="status-badge status-<?= htmlspecialchars($h['status'], ENT_QUOTES, 'UTF-8') ?> small">
              <?= htmlspecialchars(ucfirst(str_replace('_',' ',$h['status'])), ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
          <div>
            <?php if ($h['note']): ?>
              <div class="small"><?= htmlspecialchars($h['note'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.75rem">
              by <?= htmlspecialchars($h['changed_by'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
              — <?= htmlspecialchars(date('d M Y H:i', strtotime($h['changed_at'])), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
$('#updateStatusBtn').on('click', function () {
  const status = $('#quickStatus').val();
  const note   = $('#quickNote').val();
  $.post('<?= $adminBase ?>/orders/update_status.php', {
    id: <?= $id ?>,
    status: status,
    note: note,
    csrf_token: '<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>'
  }, function (res) {
    if (res.success) {
      $('#statusFeedback').html('<span class="text-success"><i class="fa fa-check me-1"></i>Status updated!</span>');
      setTimeout(() => location.reload(), 1000);
    } else {
      $('#statusFeedback').html('<span class="text-danger">' + res.message + '</span>');
    }
  }, 'json');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
