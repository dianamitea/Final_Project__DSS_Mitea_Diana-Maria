<?php
/**
 * login.php — Public customer login (P5)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isCustomerLoggedIn()) {
    header('Location: /Final_Project__DSS_Mitea_Diana-Maria/index.php');
    exit;
}

$pageTitle = 'Customer Login';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password_hash FROM customers WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();

        if ($customer && password_verify($password, $customer['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['customer_id']    = $customer['id'];
            $_SESSION['customer_name']  = $customer['first_name'] . ' ' . $customer['last_name'];
            $_SESSION['customer_email'] = $email;

            // Redirect to requested page or home
            $redirect = isset($_GET['redirect']) ? filter_var($_GET['redirect'], FILTER_SANITIZE_URL) : $base . '/index.php';
            // Only allow relative redirects within the project
            if (!str_starts_with($redirect, '/')) {
                $redirect = $base . '/index.php';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card p-4 shadow-sm">
          <div class="text-center mb-4">
            <i class="fa fa-sign-in-alt fa-3x text-primary-custom mb-2"></i>
            <h4 class="fw-bold">Customer Login</h4>
            <p class="text-muted small">Welcome back! Log in to track your orders.</p>
          </div>

          <?php if ($error): ?>
            <div id="loginError" class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          <?php else: ?>
            <div id="loginError" class="alert alert-danger" style="display:none;"></div>
          <?php endif; ?>

          <form id="loginForm" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
              <label for="login_email" class="form-label">Email Address</label>
              <input type="email" id="login_email" name="email" class="form-control"
                     value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="your@email.com" autofocus>
              <div class="invalid-feedback">Please enter your email.</div>
            </div>
            <div class="mb-3">
              <label for="login_password" class="form-label">Password</label>
              <input type="password" id="login_password" name="password" class="form-control"
                     placeholder="Your password">
              <div class="invalid-feedback">Please enter your password.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="fa fa-sign-in-alt me-2"></i>Login
            </button>
          </form>

          <p class="text-center mt-3 small">
            Don't have an account?
            <a href="<?= $base ?>/register.php" class="text-primary-custom fw-semibold">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
