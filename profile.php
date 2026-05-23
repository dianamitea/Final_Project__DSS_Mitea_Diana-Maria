<?php
/**
 * profile.php — Customer profile & order history
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();

$pageTitle = 'My Profile';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

$customerId    = (int)$_SESSION['customer_id'];
$customerEmail = $_SESSION['customer_email'];

// Fetch customer record
$stmt = $conn->prepare(
    "SELECT *, CONCAT(first_name,' ',last_name) AS full_name FROM customers WHERE id = ?"
);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$cust = $stmt->get_result()->fetch_assoc();
if (!$cust) {
    header("Location: $base/logout.php");
    exit;
}

// Handle profile update
$updateError   = '';
$updateSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!verifyCsrfToken()) { http_response_code(403); die('Invalid CSRF token.'); }
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $address   = trim($_POST['address']    ?? '');
    $city      = trim($_POST['city']       ?? '');

    if ($firstName === '' || $lastName === '') {
        $updateError = 'First and last name are required.';
    } else {
        $conn->prepare(
            "UPDATE customers SET first_name=?, last_name=?, phone=?, address=?, city=? WHERE id=?"
        )->execute_query([$firstName, $lastName, $phone, $address, $city, $customerId]);

        $_SESSION['customer_name'] = $firstName . ' ' . $lastName;
        $cust['first_name'] = $firstName;
        $cust['last_name']  = $lastName;
        $cust['full_name']  = $firstName . ' ' . $lastName;
        $cust['phone']      = $phone;
        $cust['address']    = $address;
        $cust['city']       = $city;
        $updateSuccess      = true;
    }
}

// Fetch order history
$orders = $conn->prepare(
    "SELECT id, order_code, total_price, status, delivery_date, created_at
     FROM orders WHERE customer_email = ? ORDER BY created_at DESC"
);
$orders->bind_param('s', $customerEmail);
$orders->execute();
$orderRows = $orders->get_result()->fetch_all(MYSQLI_ASSOC);

// KPIs
$totalOrders = count($orderRows);
$totalSpent  = array_sum(array_column($orderRows, 'total_price'));
$delivered   = count(array_filter($orderRows, fn($o) => $o['status'] === 'delivered'));

$statusColors = [
    'new'        => 'primary',
    'confirmed'  => 'info',
    'processing' => 'warning',
    'out_for_delivery' => 'warning',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row g-4">

    <!-- Left: Profile card -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body text-center py-4">
          <div class="rounded-circle bg-pink-light d-inline-flex align-items-center justify-content-center mb-3"
               style="width:80px;height:80px;background:#fce4ec">
            <i class="fa fa-user fa-2x" style="color:#c2185b"></i>
          </div>
          <h5 class="fw-bold mb-0"><?= htmlspecialchars($cust['full_name'], ENT_QUOTES, 'UTF-8') ?></h5>
          <p class="text-muted small mb-0"><?= htmlspecialchars($cust['email'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-muted small">Member since <?= date('F Y', strtotime($cust['created_at'])) ?></p>
        </div>
        <div class="row text-center g-0 border-top">
          <div class="col border-end py-3">
            <div class="fw-bold fs-5" style="color:#c2185b"><?= $totalOrders ?></div>
            <div class="small text-muted">Orders</div>
          </div>
          <div class="col border-end py-3">
            <div class="fw-bold fs-5" style="color:#c2185b"><?= $delivered ?></div>
            <div class="small text-muted">Delivered</div>
          </div>
          <div class="col py-3">
            <div class="fw-bold fs-5" style="color:#c2185b"><?= number_format($totalSpent, 0) ?></div>
            <div class="small text-muted">RON Spent</div>
          </div>
        </div>
      </div>

      <!-- Edit profile form -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white fw-bold border-0 pt-4 pb-2">
          <i class="fa fa-edit me-2" style="color:#c2185b"></i>Edit Profile
        </div>
        <div class="card-body">
          <?php if ($updateSuccess): ?>
            <div class="alert alert-success py-2 small">Profile updated successfully.</div>
          <?php elseif ($updateError): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($updateError, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-2">
              <label class="form-label small fw-semibold">First Name</label>
              <input type="text" name="first_name" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($cust['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Last Name</label>
              <input type="text" name="last_name" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($cust['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Phone</label>
              <input type="text" name="phone" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($cust['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Address</label>
              <input type="text" name="address" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($cust['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">City</label>
              <input type="text" name="city" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($cust['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-sm w-100 text-white fw-semibold"
                    style="background:#c2185b;border-color:#c2185b">
              <i class="fa fa-save me-1"></i>Save Changes
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right: Order history -->
    <div class="col-lg-8">
      <h4 class="fw-bold mb-3"><i class="fa fa-box-open me-2" style="color:#c2185b"></i>My Orders</h4>

      <?php if (empty($orderRows)): ?>
        <div class="card border-0 shadow-sm rounded-3 text-center py-5">
          <p class="text-muted mb-3">You haven't placed any orders yet.</p>
          <a href="<?= $base ?>/order.php" class="btn text-white px-4"
             style="background:#c2185b;border-color:#c2185b">
            <i class="fa fa-shopping-cart me-1"></i>Place Your First Order
          </a>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($orderRows as $ord): ?>
          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
              <div class="flex-grow-1">
                <div class="fw-bold"><?= htmlspecialchars($ord['order_code'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted">
                  Placed: <?= date('d M Y', strtotime($ord['created_at'])) ?> &nbsp;·&nbsp;
                  Delivery: <?= date('d M Y', strtotime($ord['delivery_date'])) ?>
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold" style="color:#c2185b"><?= number_format((float)$ord['total_price'], 2) ?> RON</div>
                <span class="badge bg-<?= $statusColors[$ord['status']] ?? 'secondary' ?>">
                  <?= ucfirst(str_replace('_', ' ', $ord['status'])) ?>
                </span>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= $base ?>/confirmation.php?code=<?= urlencode($ord['order_code']) ?>"
                   class="btn btn-sm btn-outline-secondary">
                  <i class="fa fa-eye me-1"></i>View
                </a>
                <a href="<?= $base ?>/status.php?code=<?= urlencode($ord['order_code']) ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="fa fa-map-marker-alt me-1"></i>Track
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
