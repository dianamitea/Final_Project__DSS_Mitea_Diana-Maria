<?php
/**
 * register.php — Public customer registration (P5)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isCustomerLoggedIn()) {
    header('Location: /Final_Project__DSS_Mitea_Diana-Maria/index.php');
    exit;
}

$pageTitle = 'Create an Account';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

$errors = [];
$values = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>'','address'=>'','city'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $values['first_name'] = trim($_POST['first_name'] ?? '');
    $values['last_name']  = trim($_POST['last_name']  ?? '');
    $values['email']      = trim($_POST['email']      ?? '');
    $values['phone']      = trim($_POST['phone']      ?? '');
    $values['address']    = trim($_POST['address']    ?? '');
    $values['city']       = trim($_POST['city']       ?? '');
    $password             = $_POST['password']        ?? '';
    $confirm              = $_POST['confirm_password']?? '';

    if (mb_strlen($values['first_name']) < 2) $errors['first_name'] = 'First name is too short.';
    if (mb_strlen($values['last_name'])  < 2) $errors['last_name']  = 'Last name is too short.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    if (strlen(preg_replace('/\D/', '', $values['phone'])) < 10) $errors['phone'] = 'Phone must have at least 10 digits.';
    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors['confirm'] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicate email
        $chk = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $chk->bind_param('s', $values['email']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO customers (first_name, last_name, email, phone, address, city, password_hash)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('sssssss',
            $values['first_name'], $values['last_name'], $values['email'],
            $values['phone'], $values['address'], $values['city'], $hash
        );
        if ($stmt->execute()) {
            // Auto-login
            $_SESSION['customer_id']   = $conn->insert_id;
            $_SESSION['customer_name'] = $values['first_name'] . ' ' . $values['last_name'];
            $_SESSION['customer_email']= $values['email'];
            setFlash('success', 'Welcome to Petals &amp; Bloom! Your account has been created.');
            header('Location: ' . $base . '/index.php');
            exit;
        } else {
            $errors['_general'] = 'Registration failed. Please try again.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card p-4 shadow-sm">
          <div class="text-center mb-4">
            <i class="fa fa-user-plus fa-3x text-primary-custom mb-2"></i>
            <h4 class="fw-bold">Create an Account</h4>
            <p class="text-muted small">Register to track your orders and save your details.</p>
          </div>

          <?php if (!empty($errors['_general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>

          <form id="registrationForm" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">
              <div class="col-6">
                <label for="reg_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" id="reg_first_name" name="first_name"
                       class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['first_name'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-6">
                <label for="reg_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" id="reg_last_name" name="last_name"
                       class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-12">
                <label for="reg_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="reg_email" name="email"
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-12">
                <label for="reg_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                <input type="text" id="reg_phone" name="phone"
                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-8">
                <label for="address" class="form-label">Address</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= htmlspecialchars($values['address'], ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-4">
                <label for="city" class="form-label">City</label>
                <input type="text" id="city" name="city" class="form-control"
                       value="<?= htmlspecialchars($values['city'], ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-12">
                <label for="reg_password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" id="reg_password" name="password"
                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       placeholder="At least 8 characters">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-12">
                <label for="reg_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" id="reg_confirm" name="confirm_password"
                       class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>

            <button type="submit" id="regSubmitBtn" class="btn btn-primary w-100 mt-4">
              <i class="fa fa-user-plus me-2"></i>Create Account
            </button>
          </form>

          <p class="text-center mt-3 small">
            Already have an account?
            <a href="<?= $base ?>/login.php" class="text-primary-custom fw-semibold">Login here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
