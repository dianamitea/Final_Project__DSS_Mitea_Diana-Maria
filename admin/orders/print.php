<?php
/**
 * admin/orders/print.php — Printable order receipt (no admin chrome)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$conn = getDbConnection();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { echo "Order not found."; exit; }

$itemsStmt = $conn->prepare(
    "SELECT oi.*, p.name AS product_name FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?"
);
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order <?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; font-size:13px; padding:30px; color:#333; }
    .header { text-align:center; margin-bottom:24px; border-bottom:2px solid #c2185b; padding-bottom:16px; }
    .header h1 { font-size:22px; color:#c2185b; }
    .header p { color:#888; margin-top:4px; }
    .section { margin-bottom:20px; }
    .section h3 { font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#aaa; margin-bottom:8px; }
    .row { display:flex; gap:32px; }
    .col { flex:1; }
    table { width:100%; border-collapse:collapse; margin-top:8px; }
    th { background:#f5f5f5; padding:8px 10px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
    td { padding:8px 10px; border-bottom:1px solid #eee; }
    .total-row td { font-weight:bold; font-size:15px; border-top:2px solid #c2185b; }
    .badge { display:inline-block; padding:3px 10px; border-radius:10px; font-size:11px; font-weight:600; }
    .status-delivered { background:#e8f5e9; color:#1b5e20; }
    .status-pending   { background:#fff3e0; color:#e65100; }
    .status-new       { background:#e3f2fd; color:#1565c0; }
    .footer { margin-top:40px; text-align:center; color:#aaa; font-size:11px; }
    @media print {
      .no-print { display:none !important; }
      body { padding:0; }
    }
  </style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;">
  <button onclick="window.print()" style="background:#c2185b;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:14px;">
    🖨 Print
  </button>
  <a href="javascript:window.close()" style="margin-left:10px;font-size:14px;color:#888;">Close</a>
</div>

<div class="header">
  <h1>🌸 Petals &amp; Bloom</h1>
  <p>Order Confirmation</p>
  <p style="font-size:18px;font-weight:700;color:#333;margin-top:8px">
    <?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?>
  </p>
</div>

<div class="section row">
  <div class="col">
    <h3>Customer</h3>
    <p><strong><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p><?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="col">
    <h3>Delivery</h3>
    <p><?= nl2br(htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8')) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars(date('d M Y', strtotime($order['delivery_date'])), ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="col">
    <h3>Order Info</h3>
    <p><strong>Placed:</strong> <?= htmlspecialchars(date('d M Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Occasion:</strong> <?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['occasion']??'')), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Payment:</strong> <?= htmlspecialchars(ucfirst($order['payment_method']??''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong>
      <span class="badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['status'])), ENT_QUOTES, 'UTF-8') ?>
      </span>
    </p>
    <?php if ($order['card_message']): ?>
    <p style="margin-top:8px;font-style:italic;color:#555">"<?= htmlspecialchars($order['card_message'], ENT_QUOTES, 'UTF-8') ?>"</p>
    <?php endif; ?>
  </div>
</div>

<div class="section">
  <h3>Order Items</h3>
  <table>
    <thead>
      <tr><th>Product</th><th style="text-align:right">Unit Price</th><th style="text-align:center">Qty</th><th style="text-align:right">Subtotal</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['product_name'] ?? '(deleted)', ENT_QUOTES, 'UTF-8') ?></td>
        <td style="text-align:right"><?= number_format((float)$it['unit_price'], 2) ?> RON</td>
        <td style="text-align:center"><?= (int)$it['quantity'] ?></td>
        <td style="text-align:right"><?= number_format((float)$it['subtotal'], 2) ?> RON</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="3" style="text-align:right">TOTAL</td>
        <td style="text-align:right;color:#c2185b"><?= number_format((float)$order['total_price'], 2) ?> RON</td>
      </tr>
    </tfoot>
  </table>
</div>

<div class="footer">
  <p>Petals &amp; Bloom — Bringing Joy Through Flowers</p>
  <p>Thank you for your order!</p>
</div>

</body>
</html>
