<?php
/**
 * admin/login.php — Admin login page (A1)
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isAdminLoggedIn()) {
    header('Location: /Final_Project__DSS_Mitea_Diana-Maria/admin/index.php');
    exit;
}

$error = '';
$conn  = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfOk   = !empty($_POST['csrf_token'])
                && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);

    if (!$csrfOk) {
        $error = 'Security check failed. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, password_hash FROM admins WHERE username = ?"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: /Final_Project__DSS_Mitea_Diana-Maria/admin/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$adminBase = $base . '/admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Petals &amp; Bloom</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg,#880e4f,#c2185b); min-height:100vh; display:flex; align-items:center; }
    .login-card { max-width:400px; width:100%; border-radius:16px; border:none; box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .login-logo { width:80px; height:80px; background:#fce4ec; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
    .btn-admin { background:#c2185b; border-color:#c2185b; }
    .btn-admin:hover { background:#880e4f; border-color:#880e4f; }
    .form-control:focus { border-color:#c2185b; box-shadow:0 0 0 .2rem rgba(194,24,91,.2); }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-8 col-md-5">
      <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="login-logo">
            <i class="fa fa-spa fa-2x text-danger"></i>
          </div>
          <h4 class="fw-bold mb-0">Petals &amp; Bloom</h4>
          <p class="text-muted small mt-1">Admin Panel</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa fa-user text-muted"></i></span>
              <input type="text" name="username" class="form-control" autofocus
                     value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="admin">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa fa-lock text-muted"></i></span>
              <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>
          </div>
          <button type="submit" class="btn btn-admin btn-primary w-100 text-white fw-semibold py-2">
            <i class="fa fa-sign-in-alt me-2"></i>Login to Admin Panel
          </button>
        </form>

        <p class="text-center mt-3 small text-muted">
          <a href="<?= $base ?>/index.php" class="text-muted">
            <i class="fa fa-arrow-left me-1"></i>Back to website
          </a>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
