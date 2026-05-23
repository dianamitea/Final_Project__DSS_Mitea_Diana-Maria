<?php
/**
 * admin/orders/edit.php — Edit order details (A4)
 * 5+ fields with validation and flash messages
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    setFlash('danger', 'Order not found.');
    header("Location: $adminBase/orders/index.php");
    exit;
}

$adminPageTitle = 'Edit ' . $order['order_code'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $name       = trim($_POST['customer_name']    ?? '');
        $email      = trim($_POST['customer_email']   ?? '');
        $phone      = trim($_POST['customer_phone']   ?? '');
        $address    = trim($_POST['delivery_address'] ?? '');
        $delivDate  = trim($_POST['delivery_date']    ?? '');
        $occasion   = $_POST['occasion']              ?? 'other';
        $payMethod  = $_POST['payment_method']        ?? 'cash';
        $payStatus  = $_POST['payment_status']        ?? 'unpaid';
        $cardMsg    = trim($_POST['card_message']     ?? '');
        $specReq    = trim($_POST['special_notes'] ?? '');
        $status     = $_POST['status']                ?? $order['status'];

        if (strlen($name) < 3)     $errors['customer_name']    = 'Name must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['customer_email'] = 'Invalid email.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors['customer_phone'] = 'Invalid phone.';
        if (empty($address))       $errors['delivery_address'] = 'Address is required.';
        if (empty($delivDate))     $errors['delivery_date']    = 'Delivery date is required.';

        if (empty($errors)) {
            $u = $conn->prepare(
                "UPDATE orders SET customer_name=?, customer_email=?, customer_phone=?,
                  delivery_address=?, delivery_date=?, occasion=?, payment_method=?,
                  payment_status=?, card_message=?, special_notes=?, status=?
                 WHERE id=?"
            );
            $u->bind_param('sssssssssssi',
                $name, $email, $phone, $address, $delivDate,
                $occasion, $payMethod, $payStatus, $cardMsg, $specReq, $status, $id
            );
            if ($u->execute()) {
                // Log status change if status changed
                if ($status !== $order['status']) {
                    $adminName = $_SESSION['admin_name'];
                    $conn->prepare(
                        "INSERT INTO status_history (order_id, new_status, changed_by, notes) VALUES (?,?,?,?)"
                    )->execute_query([$id, $status, $adminName, "Status changed via order edit."]);
                }
                setFlash('success', 'Order updated successfully.');
                header("Location: $adminBase/orders/view.php?id=$id");
                exit;
            } else {
                $errors['_general'] = 'Database update failed.';
            }
        }
        // Repopulate order for form on error
        $order = array_merge($order, compact('name','email','phone','address','delivDate','occasion','payMethod','payStatus','cardMsg','specReq','status'));
        $order['customer_name']    = $name;
        $order['customer_email']   = $email;
        $order['customer_phone']   = $phone;
        $order['delivery_address'] = $address;
        $order['delivery_date']    = $delivDate;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-edit me-2 text-primary-custom"></i>Edit <?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></h1>
  <a href="<?= $adminBase ?>/orders/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back to Order
  </a>
</div>

<?php if (!empty($errors['_general'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="id" value="<?= $id ?>">

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Customer Information</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Customer Name *</label>
            <input type="text" name="customer_name"
                   class="form-control <?= isset($errors['customer_name'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" name="customer_email"
                   class="form-control <?= isset($errors['customer_email'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($order['customer_email'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_email'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone *</label>
            <input type="tel" name="customer_phone"
                   class="form-control <?= isset($errors['customer_phone'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['customer_phone'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_phone'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Occasion</label>
            <select name="occasion" class="form-select">
              <?php foreach (['birthday','anniversary','wedding','valentine','mothers_day','corporate','funeral','other'] as $occ): ?>
                <option value="<?= $occ ?>" <?= ($order['occasion'] ?? '') === $occ ? 'selected' : '' ?>>
                  <?= ucfirst(str_replace('_',' ',$occ)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Delivery Address *</label>
            <textarea name="delivery_address" rows="2"
                      class="form-control <?= isset($errors['delivery_address'])?'is-invalid':'' ?>"
                      required><?= htmlspecialchars($order['delivery_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['delivery_address'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_address'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Delivery Date *</label>
            <input type="date" name="delivery_date"
                   class="form-control <?= isset($errors['delivery_date'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($order['delivery_date'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['delivery_date'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_date'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Card Message</label>
            <input type="text" name="card_message" class="form-control"
                   value="<?= htmlspecialchars($order['card_message'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Special Requests</label>
            <textarea name="special_notes" rows="2" class="form-control"><?= htmlspecialchars($order['special_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Order Status</div>
        <select name="status" class="form-select">
          <?php foreach (['new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
              <?= ucfirst(str_replace('_',' ',$s)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="admin-form-card mb-4">
        <div class="form-section-title">Payment</div>
        <div class="mb-3">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-select">
            <option value="cash"          <?= ($order['payment_method']??'') === 'cash'?'selected':'' ?>>Cash on Delivery</option>
            <option value="card"          <?= ($order['payment_method']??'') === 'card'?'selected':'' ?>>Card</option>
            <option value="bank_transfer" <?= ($order['payment_method']??'') === 'bank_transfer'?'selected':'' ?>>Bank Transfer</option>
          </select>
        </div>
        <div>
          <label class="form-label">Payment Status</label>
          <select name="payment_status" class="form-select">
            <option value="unpaid"   <?= ($order['payment_status']??'') === 'unpaid'?'selected':'' ?>>Unpaid</option>
            <option value="paid"     <?= ($order['payment_status']??'') === 'paid'?'selected':'' ?>>Paid</option>
            <option value="refunded" <?= ($order['payment_status']??'') === 'refunded'?'selected':'' ?>>Refunded</option>
          </select>
        </div>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary py-2 fw-semibold">
          <i class="fa fa-save me-2"></i>Save Changes
        </button>
      </div>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
