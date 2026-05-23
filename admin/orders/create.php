<?php
/**
 * admin/orders/create.php — Create new order (admin side, A2)
 * 8+ fields, 5+ input types, MySQL transaction
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'New Order';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// Load products + categories for form
$products   = $conn->query("SELECT id, name, price, stock_quantity FROM products WHERE is_active=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$customers  = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) AS full_name, email FROM customers ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$form   = [
    'customer_name'    => '',
    'customer_email'   => '',
    'customer_phone'   => '',
    'delivery_address' => '',
    'delivery_date'    => '',
    'occasion'         => 'birthday',
    'payment_method'   => 'cash',
    'payment_status'   => 'unpaid',
    'card_message'     => '',
    'special_requests' => '',
    'items'            => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $form = array_merge($form, [
            'customer_name'    => trim($_POST['customer_name']    ?? ''),
            'customer_email'   => trim($_POST['customer_email']   ?? ''),
            'customer_phone'   => trim($_POST['customer_phone']   ?? ''),
            'delivery_address' => trim($_POST['delivery_address'] ?? ''),
            'delivery_date'    => trim($_POST['delivery_date']    ?? ''),
            'occasion'         => $_POST['occasion']              ?? 'other',
            'payment_method'   => $_POST['payment_method']        ?? 'cash',
            'payment_status'   => $_POST['payment_status']        ?? 'unpaid',
            'card_message'     => trim($_POST['card_message']     ?? ''),
            'special_requests' => trim($_POST['special_requests'] ?? ''),
        ]);

        // Collect items
        $selectedItems = [];
        $productIds    = $_POST['product_id']  ?? [];
        $quantities    = $_POST['quantity']     ?? [];
        foreach ($productIds as $k => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$k] ?? 1);
            if ($pid > 0 && $qty > 0) {
                $selectedItems[$pid] = $qty;
            }
        }
        $form['items'] = $selectedItems;

        // Validate
        if (strlen($form['customer_name']) < 3)
            $errors['customer_name'] = 'Name must be at least 3 characters.';
        if (!filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL))
            $errors['customer_email'] = 'Invalid email address.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $form['customer_phone']))
            $errors['customer_phone'] = 'Invalid phone number.';
        if (empty($form['delivery_address']))
            $errors['delivery_address'] = 'Delivery address is required.';
        if (empty($form['delivery_date']))
            $errors['delivery_date'] = 'Delivery date is required.';
        elseif (strtotime($form['delivery_date']) < strtotime('today'))
            $errors['delivery_date'] = 'Delivery date must be today or in the future.';
        if (empty($selectedItems))
            $errors['items'] = 'Please select at least one product.';

        if (empty($errors)) {
            // Compute totals from DB
            $itemData   = [];
            $totalPrice = 0.0;
            $valid      = true;

            foreach ($selectedItems as $pid => $qty) {
                $stmt = $conn->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id=? AND is_active=1");
                $stmt->bind_param('i', $pid);
                $stmt->execute();
                $prod = $stmt->get_result()->fetch_assoc();
                if (!$prod) { $errors['items'] = "Product #$pid not found."; $valid = false; break; }
                if ($prod['stock_quantity'] < $qty) {
                    $errors['items'] = "Not enough stock for {$prod['name']}."; $valid = false; break;
                }
                $lineTotal   = $prod['price'] * $qty;
                $totalPrice += $lineTotal;
                $itemData[]  = ['id' => $pid, 'name' => $prod['name'], 'qty' => $qty, 'price' => $prod['price'], 'line' => $lineTotal];
            }

            if ($valid) {
                // MySQL TRANSACTION (A2)
                $conn->begin_transaction();
                try {
                    // Insert order with temp code
                    $s = $conn->prepare(
                        "INSERT INTO orders (order_code, customer_name, customer_email, customer_phone,
                          delivery_address, delivery_date, occasion, payment_method, payment_status,
                          card_message, special_requests, status, total_price)
                         VALUES ('TEMP','?','?','?','?','?','?','?','?','?','?','new',?)"
                    );
                    // rebuild with correct placeholders
                    $s = $conn->prepare(
                        "INSERT INTO orders (order_code, customer_name, customer_email, customer_phone,
                          delivery_address, delivery_date, occasion, payment_method, payment_status,
                          card_message, special_requests, status, total_price)
                         VALUES ('TEMP',?,?,?,?,?,?,?,?,?,?,'new',?)"
                    );
                    $s->bind_param('ssssssssssd',
                        $form['customer_name'], $form['customer_email'], $form['customer_phone'],
                        $form['delivery_address'], $form['delivery_date'], $form['occasion'],
                        $form['payment_method'], $form['payment_status'],
                        $form['card_message'], $form['special_requests'], $totalPrice
                    );
                    $s->execute();
                    $orderId   = $conn->insert_id;
                    $orderCode = 'ORD-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);

                    $conn->prepare("UPDATE orders SET order_code=? WHERE id=?")->execute_query([$orderCode, $orderId]);

                    // Insert order items
                    $si = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)");
                    foreach ($itemData as $it) {
                        $si->bind_param('iiddd', $orderId, $it['id'], $it['qty'], $it['price'], $it['line']);
                        $si->execute();
                    }

                    // Update stock
                    $su = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id=?");
                    foreach ($itemData as $it) {
                        $su->bind_param('ii', $it['qty'], $it['id']);
                        $su->execute();
                    }

                    // Status history
                    $admin_id   = (int)$_SESSION['admin_id'];
                    $adminName  = $_SESSION['admin_name'];
                    $noteText   = "Order created by admin: $adminName";
                    $conn->prepare(
                        "INSERT INTO status_history (order_id, status, changed_by, note) VALUES (?,'new',?,?)"
                    )->execute_query([$orderId, $adminName, $noteText]);

                    $conn->commit();

                    setFlash('success', "Order $orderCode created successfully!");
                    header("Location: $adminBase/orders/view.php?id=$orderId");
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors['_general'] = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-plus me-2 text-primary-custom"></i>New Order</h1>
  <a href="<?= $adminBase ?>/orders/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back to Orders
  </a>
</div>

<?php if (!empty($errors['_general'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" id="createOrderForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

  <div class="row g-4">
    <!-- Left column: Customer + Delivery -->
    <div class="col-lg-7">
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Customer Information</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Customer Name *</label>
            <input type="text" name="customer_name" class="form-control <?= isset($errors['customer_name'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['customer_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" name="customer_email" class="form-control <?= isset($errors['customer_email'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['customer_email'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_email'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone *</label>
            <input type="tel" name="customer_phone" class="form-control <?= isset($errors['customer_phone'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['customer_phone'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_phone'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_phone'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Occasion</label>
            <select name="occasion" class="form-select">
              <?php foreach (['birthday','anniversary','wedding','valentine','mothers_day','corporate','funeral','other'] as $occ): ?>
                <option value="<?= $occ ?>" <?= $form['occasion'] === $occ ? 'selected' : '' ?>>
                  <?= ucfirst(str_replace('_', ' ', $occ)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Delivery Address *</label>
            <textarea name="delivery_address" rows="2"
                      class="form-control <?= isset($errors['delivery_address'])?'is-invalid':'' ?>"
                      required><?= htmlspecialchars($form['delivery_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['delivery_address'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_address'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Delivery Date *</label>
            <input type="date" name="delivery_date"
                   class="form-control <?= isset($errors['delivery_date'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['delivery_date'], ENT_QUOTES, 'UTF-8') ?>"
                   min="<?= date('Y-m-d') ?>" required>
            <?php if (isset($errors['delivery_date'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_date'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Card Message</label>
            <input type="text" name="card_message" class="form-control"
                   value="<?= htmlspecialchars($form['card_message'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Special Requests</label>
            <textarea name="special_requests" rows="2" class="form-control"><?= htmlspecialchars($form['special_requests'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>

      <div class="admin-form-card">
        <div class="form-section-title">Payment</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="cash"          <?= $form['payment_method']==='cash'?'selected':'' ?>>Cash on Delivery</option>
              <option value="card"          <?= $form['payment_method']==='card'?'selected':'' ?>>Card</option>
              <option value="bank_transfer" <?= $form['payment_method']==='bank_transfer'?'selected':'' ?>>Bank Transfer</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select">
              <option value="unpaid" <?= $form['payment_status']==='unpaid'?'selected':'' ?>>Unpaid</option>
              <option value="paid"   <?= $form['payment_status']==='paid'?'selected':'' ?>>Paid</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Right column: Products -->
    <div class="col-lg-5">
      <div class="admin-form-card">
        <div class="form-section-title">Products *</div>
        <?php if (isset($errors['items'])): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errors['items'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div id="productLines">
          <div class="product-line row g-2 mb-2 align-items-center">
            <div class="col-7">
              <select name="product_id[]" class="form-select form-select-sm product-select">
                <option value="">-- Select product --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                    <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float)$p['price'],2) ?> RON)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-3">
              <input type="number" name="quantity[]" class="form-control form-control-sm qty-input"
                     value="1" min="1" placeholder="Qty">
            </div>
            <div class="col-2">
              <button type="button" class="btn btn-outline-danger btn-sm remove-line"><i class="fa fa-minus"></i></button>
            </div>
          </div>
        </div>
        <button type="button" id="addProductLine" class="btn btn-outline-primary btn-sm mt-1">
          <i class="fa fa-plus me-1"></i>Add Product
        </button>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Order Total:</span>
          <span id="orderTotalDisplay" class="text-primary-custom">0.00 RON</span>
        </div>
      </div>

      <div class="mt-4 d-grid">
        <button type="submit" class="btn btn-primary py-2 fw-semibold">
          <i class="fa fa-check me-2"></i>Create Order
        </button>
      </div>
    </div>
  </div>
</form>

<script>
const productLineTpl = `<div class="product-line row g-2 mb-2 align-items-center">
  <div class="col-7">
    <select name="product_id[]" class="form-select form-select-sm product-select">
      <option value="">-- Select product --</option>
      <?php foreach ($products as $p): ?>
      <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
        <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float)$p['price'],2) ?> RON)
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-3">
    <input type="number" name="quantity[]" class="form-control form-control-sm qty-input" value="1" min="1">
  </div>
  <div class="col-2">
    <button type="button" class="btn btn-outline-danger btn-sm remove-line"><i class="fa fa-minus"></i></button>
  </div>
</div>`;

function recalcTotal() {
  let total = 0;
  $('#productLines .product-line').each(function () {
    const price = parseFloat($(this).find('.product-select option:selected').data('price') || 0);
    const qty   = parseInt($(this).find('.qty-input').val() || 0);
    total += price * qty;
  });
  $('#orderTotalDisplay').text(total.toFixed(2) + ' RON');
}

$(function () {
  $('#addProductLine').on('click', function () {
    $('#productLines').append(productLineTpl);
    recalcTotal();
  });
  $(document).on('click', '.remove-line', function () {
    if ($('#productLines .product-line').length > 1) {
      $(this).closest('.product-line').remove();
      recalcTotal();
    }
  });
  $(document).on('change input', '.product-select, .qty-input', recalcTotal);
  recalcTotal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
