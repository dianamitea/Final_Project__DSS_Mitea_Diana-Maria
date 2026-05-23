<?php
/**
 * order.php — Public order form (P3, P4, P6)
 * POST form with 7+ fields, 5+ input types, server-side validation,
 * transaction on insert, and redirect to confirmation.php on success.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Place an Order';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

// ── Fetch all active products for the form ──
$productsRes = $conn->query(
    "SELECT p.id, p.name, p.price, p.stock_quantity, c.name AS cat_name
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.is_active = 1 AND p.stock_quantity > 0
     ORDER BY c.name, p.name"
);
$allProducts = [];
while ($row = $productsRes->fetch_assoc()) {
    $allProducts[] = $row;
}

// Pre-select product if coming from product detail page
$preselect = (isset($_GET['product_id']) && ctype_digit((string)$_GET['product_id']))
    ? (int)$_GET['product_id'] : null;

// ── Form state ──
$errors  = [];
$success = false;
$values  = [
    'customer_name'    => '',
    'customer_email'   => '',
    'customer_phone'   => '',
    'delivery_address' => '',
    'delivery_city'    => '',
    'delivery_date'    => '',
    'delivery_time'    => '',
    'occasion'         => '',
    'card_message'     => '',
    'special_notes'    => '',
];

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verifyCsrfToken()) { http_response_code(403); die('Invalid CSRF token.'); }

    // Read & sanitize
    $values['customer_name']    = trim($_POST['customer_name']    ?? '');
    $values['customer_email']   = trim($_POST['customer_email']   ?? '');
    $values['customer_phone']   = trim($_POST['customer_phone']   ?? '');
    $values['delivery_address'] = trim($_POST['delivery_address'] ?? '');
    $values['delivery_city']    = trim($_POST['delivery_city']    ?? '');
    $values['delivery_date']    = trim($_POST['delivery_date']    ?? '');
    $values['delivery_time']    = trim($_POST['delivery_time']    ?? '');
    $values['occasion']         = trim($_POST['occasion']         ?? '');
    $values['card_message']     = trim($_POST['card_message']     ?? '');
    $values['special_notes']    = trim($_POST['special_notes']    ?? '');
    $agreeTerms                 = isset($_POST['agree_terms']);

    // ── Server-side validation ──
    if (mb_strlen($values['customer_name']) < 2) {
        $errors['customer_name'] = 'Full name must be at least 2 characters.';
    }
    if (!filter_var($values['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['customer_email'] = 'Please enter a valid email address.';
    }
    if (strlen(preg_replace('/\D/', '', $values['customer_phone'])) < 10) {
        $errors['customer_phone'] = 'Phone number must have at least 10 digits.';
    }
    if (mb_strlen($values['delivery_address']) < 5) {
        $errors['delivery_address'] = 'Delivery address is required.';
    }
    if (mb_strlen($values['delivery_city']) < 2) {
        $errors['delivery_city'] = 'City is required.';
    }
    if (!$values['delivery_date']) {
        $errors['delivery_date'] = 'Delivery date is required.';
    } else {
        $d = new DateTime($values['delivery_date']);
        $today = new DateTime('today');
        if ($d <= $today) {
            $errors['delivery_date'] = 'Delivery date must be in the future.';
        }
    }
    if ($values['delivery_time'] === '') {
        $errors['delivery_time'] = 'Please select a delivery time.';
    }
    if ($values['occasion'] === '') {
        $errors['occasion'] = 'Please select an occasion.';
    }
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must agree to the terms and conditions.';
    }

    // Products
    $selectedProducts = [];
    $totalPrice       = 0.0;
    if (!empty($_POST['products']) && is_array($_POST['products'])) {
        foreach ($_POST['products'] as $pid) {
            if (!ctype_digit((string)$pid)) continue;
            $pid = (int)$pid;
            // Look up in our list
            foreach ($allProducts as $ap) {
                if ((int)$ap['id'] === $pid) {
                    $qty = max(1, (int)($_POST['qty_' . $pid] ?? 1));
                    $qty = min($qty, (int)$ap['stock_quantity']); // cap at stock
                    $sub = round($ap['price'] * $qty, 2);
                    $totalPrice += $sub;
                    $selectedProducts[] = [
                        'id'    => $pid,
                        'name'  => $ap['name'],
                        'price' => $ap['price'],
                        'qty'   => $qty,
                        'sub'   => $sub,
                    ];
                    break;
                }
            }
        }
    }
    if (empty($selectedProducts)) {
        $errors['products'] = 'Please select at least one product.';
    }

    // ── Insert if valid ──
    if (empty($errors)) {
        $customerId = isCustomerLoggedIn() ? (int)$_SESSION['customer_id'] : null;
        $conn->begin_transaction();
        try {
            // 1. Insert order (order_code filled after we get the ID)
            $stmt = $conn->prepare(
                "INSERT INTO orders
                 (customer_id, order_code, customer_name, customer_email, customer_phone,
                  delivery_address, delivery_city, delivery_date, delivery_time,
                  occasion, card_message, special_notes, total_price)
                 VALUES (?, 'TEMP', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'issssssssssd',
                $customerId,
                $values['customer_name'],
                $values['customer_email'],
                $values['customer_phone'],
                $values['delivery_address'],
                $values['delivery_city'],
                $values['delivery_date'],
                $values['delivery_time'],
                $values['occasion'],
                $values['card_message'],
                $values['special_notes'],
                $totalPrice
            );
            $stmt->execute();
            $orderId   = $conn->insert_id;
            $orderCode = 'ORD-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);

            // 2. Update order code
            $stmt2 = $conn->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
            $stmt2->bind_param('si', $orderCode, $orderId);
            $stmt2->execute();

            // 3. Insert order items
            $stmt3 = $conn->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($selectedProducts as $sp) {
                $stmt3->bind_param('iisidd', $orderId, $sp['id'], $sp['name'], $sp['qty'], $sp['price'], $sp['sub']);
                $stmt3->execute();

                // Reduce stock
                $stk = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stk->bind_param('ii', $sp['qty'], $sp['id']);
                $stk->execute();
            }

            // 4. Insert initial status history
            $stmt4 = $conn->prepare(
                "INSERT INTO status_history (order_id, new_status, changed_by, notes)
                 VALUES (?, 'new', 'system', 'Order placed online')"
            );
            $stmt4->bind_param('i', $orderId);
            $stmt4->execute();

            $conn->commit();

            // Redirect to confirmation page
            header('Location: ' . $base . '/confirmation.php?code=' . urlencode($orderCode));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Order insert failed: ' . $e->getMessage());
            $errors['_general'] = 'An error occurred while placing your order. Please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container">
    <h1 class="fw-bold text-primary-custom mb-1">Place an Order</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $base ?>/index.php">Home</a></li>
        <li class="breadcrumb-item active">Order</li>
      </ol>
    </nav>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <?php if (!empty($errors['_general'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="order-form-section">
          <h4 class="fw-bold mb-4"><i class="fa fa-shopping-cart me-2 text-primary-custom"></i>Your Order Details</h4>

          <form id="orderForm" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <!-- Contact info -->
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.8rem;letter-spacing:1px;">
              1. Contact Information
            </h6>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="customer_name" name="customer_name" class="form-control <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['customer_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Ana Popescu">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label for="customer_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="customer_email" name="customer_email" class="form-control <?= isset($errors['customer_email']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['customer_email'], ENT_QUOTES, 'UTF-8') ?>" placeholder="email@example.com">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label for="customer_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                <input type="text" id="customer_phone" name="customer_phone" class="form-control <?= isset($errors['customer_phone']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['customer_phone'], ENT_QUOTES, 'UTF-8') ?>" placeholder="07xx xxx xxx">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <!-- Delivery info -->
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.8rem;letter-spacing:1px;">
              2. Delivery Information
            </h6>
            <div class="row g-3 mb-4">
              <div class="col-md-8">
                <label for="delivery_address" class="form-label">Delivery Address <span class="text-danger">*</span></label>
                <input type="text" id="delivery_address" name="delivery_address" class="form-control <?= isset($errors['delivery_address']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['delivery_address'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Street name, number, apartment">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label for="delivery_city" class="form-label">City <span class="text-danger">*</span></label>
                <input type="text" id="delivery_city" name="delivery_city" class="form-control <?= isset($errors['delivery_city']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['delivery_city'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Bucharest">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_city'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                <input type="date" id="delivery_date" name="delivery_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="form-control <?= isset($errors['delivery_date']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['delivery_date'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label for="delivery_time" class="form-label">Preferred Delivery Time <span class="text-danger">*</span></label>
                <select id="delivery_time" name="delivery_time" class="form-select <?= isset($errors['delivery_time']) ? 'is-invalid' : '' ?>">
                  <option value="">-- Select time --</option>
                  <option value="morning"   <?= $values['delivery_time'] === 'morning'   ? 'selected' : '' ?>>Morning (08:00–12:00)</option>
                  <option value="afternoon" <?= $values['delivery_time'] === 'afternoon' ? 'selected' : '' ?>>Afternoon (12:00–17:00)</option>
                  <option value="evening"   <?= $values['delivery_time'] === 'evening'   ? 'selected' : '' ?>>Evening (17:00–20:00)</option>
                </select>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_time'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label for="occasion" class="form-label">Occasion <span class="text-danger">*</span></label>
                <select id="occasion" name="occasion" class="form-select <?= isset($errors['occasion']) ? 'is-invalid' : '' ?>">
                  <option value="">-- Select occasion --</option>
                  <?php foreach (['birthday' => "Birthday", 'anniversary' => "Anniversary", 'wedding' => "Wedding",
                      'valentine' => "Valentine's Day", 'mothers_day' => "Mother's Day",
                      'corporate' => "Corporate / Business", 'funeral' => "Funeral / Sympathy",
                      'other' => "Other"] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $values['occasion'] === $v ? 'selected' : '' ?>>
                      <?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['occasion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <!-- Products -->
            <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.8rem;letter-spacing:1px;">
              3. Select Products <span class="text-danger">*</span>
            </h6>
            <div class="mb-1">
              <div id="products-error" class="text-danger small mb-2"
                   <?= isset($errors['products']) ? '' : 'style="display:none"' ?>>
                <?= htmlspecialchars($errors['products'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </div>
              <?php foreach ($allProducts as $ap): ?>
              <div class="product-checkbox-item d-flex align-items-center gap-3">
                <input type="checkbox" class="form-check-input"
                       name="products[]"
                       id="prod_<?= (int)$ap['id'] ?>"
                       value="<?= (int)$ap['id'] ?>"
                       data-price="<?= (float)$ap['price'] ?>"
                       <?= ($preselect === (int)$ap['id'] || in_array((int)$ap['id'], array_column($selectedProducts ?? [], 'id') ?? [])) ? 'checked' : '' ?>>
                <label for="prod_<?= (int)$ap['id'] ?>" class="flex-grow-1 mb-0 d-flex justify-content-between align-items-center" style="cursor:pointer">
                  <span>
                    <?= htmlspecialchars($ap['name'], ENT_QUOTES, 'UTF-8') ?>
                    <small class="text-muted">(<?= htmlspecialchars($ap['cat_name'], ENT_QUOTES, 'UTF-8') ?>)</small>
                  </span>
                  <strong class="text-primary-custom ms-3"><?= number_format((float)$ap['price'], 2) ?> RON</strong>
                </label>
                <!-- Quantity stepper -->
                <div class="d-flex align-items-center gap-1" style="min-width:100px;">
                  <button type="button" class="btn btn-sm btn-outline-secondary qty-minus px-2">−</button>
                  <input type="number" name="qty_<?= (int)$ap['id'] ?>" class="form-control form-control-sm qty-input text-center"
                         value="1" min="1" max="<?= (int)$ap['stock_quantity'] ?>" style="width:52px;">
                  <button type="button" class="btn btn-sm btn-outline-secondary qty-plus px-2">+</button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Order total preview -->
            <div id="orderTotalWrapper" class="text-end mt-3 p-3 rounded" style="background:var(--primary-light); display:none!important;">
              <strong>Estimated Total: <span id="orderTotal" class="text-primary-custom fs-5"></span></strong>
            </div>

            <!-- Card message & notes -->
            <div class="row g-3 mt-2 mb-4">
              <div class="col-md-6">
                <label for="card_message" class="form-label">Card Message</label>
                <textarea id="card_message" name="card_message" class="form-control" rows="3"
                          placeholder="Message to include on the greeting card…"><?= htmlspecialchars($values['card_message'], ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
              <div class="col-md-6">
                <label for="special_notes" class="form-label">Special Instructions</label>
                <textarea id="special_notes" name="special_notes" class="form-control" rows="3"
                          placeholder="Any special requests or delivery instructions…"><?= htmlspecialchars($values['special_notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
            </div>

            <!-- Terms -->
            <div class="form-check mb-4">
              <input type="checkbox" class="form-check-input <?= isset($errors['agree_terms']) ? 'is-invalid' : '' ?>"
                     id="agree_terms" name="agree_terms"
                     <?= (!empty($_POST['agree_terms'])) ? 'checked' : '' ?>>
              <label class="form-check-label" for="agree_terms">
                I agree to the <a href="#" class="text-primary-custom">Terms &amp; Conditions</a> and confirm that my details are correct.
              </label>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['agree_terms'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg w-100">
              <i class="fa fa-gift me-2"></i>Place My Order
            </button>
          </form>
        </div>
      </div>

      <!-- Info sidebar -->
      <div class="col-lg-4">
        <div class="card p-4 mb-3">
          <h6 class="fw-semibold mb-3"><i class="fa fa-truck me-2 text-primary-custom"></i>Delivery Info</h6>
          <ul class="list-unstyled small text-muted">
            <li class="mb-2"><i class="fa fa-check text-success me-2"></i>Free delivery within Bucharest over 200 RON</li>
            <li class="mb-2"><i class="fa fa-check text-success me-2"></i>Same-day delivery for orders before 12:00</li>
            <li class="mb-2"><i class="fa fa-check text-success me-2"></i>Nationwide delivery available</li>
            <li class="mb-2"><i class="fa fa-check text-success me-2"></i>Delivery confirmation by SMS</li>
          </ul>
        </div>
        <div class="card p-4">
          <h6 class="fw-semibold mb-3"><i class="fa fa-shield-alt me-2 text-primary-custom"></i>Secure Ordering</h6>
          <p class="small text-muted mb-0">Your order is processed securely. We never store payment card details. You'll receive an order code to track your delivery.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
