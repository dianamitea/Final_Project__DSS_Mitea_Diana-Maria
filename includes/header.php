<?php
/**
 * Public site shared header.
 * Include at the top of every public page AFTER setting $pageTitle.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Petals & Bloom';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';

// Determine the current page for nav active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Petals &amp; Bloom</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="<?= $base ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ── Topbar ── -->
<div class="topbar">
  <div class="container d-flex justify-content-between align-items-center py-1">
    <small><i class="fa fa-phone me-1"></i> +40 721 000 111</small>
    <small><i class="fa fa-envelope me-1"></i> hello@petalsandbloom.ro</small>
    <small>
      <?php if (isCustomerLoggedIn()): ?>
        <i class="fa fa-user me-1"></i>
        Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') ?>!
        <a href="<?= $base ?>/logout.php" class="text-white ms-2"><i class="fa fa-sign-out-alt"></i> Logout</a>
      <?php else: ?>
        <a href="<?= $base ?>/login.php" class="text-white me-2"><i class="fa fa-sign-in-alt"></i> Login</a>
        <a href="<?= $base ?>/register.php" class="text-white"><i class="fa fa-user-plus"></i> Register</a>
      <?php endif; ?>
    </small>
  </div>
</div>

<!-- ── Navbar ── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $base ?>/index.php">
      <i class="fa fa-spa fs-4"></i>
      <span class="brand-text">Petals &amp; Bloom</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
             href="<?= $base ?>/index.php">
            <i class="fa fa-home me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>"
             href="<?= $base ?>/products.php">
            <i class="fa fa-leaf me-1"></i>Flowers
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'order.php' ? 'active' : '' ?>"
             href="<?= $base ?>/order.php">
            <i class="fa fa-shopping-cart me-1"></i>Order Now
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'status.php' ? 'active' : '' ?>"
             href="<?= $base ?>/status.php">
            <i class="fa fa-search me-1"></i>Track Order
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>"
             href="<?= $base ?>/contact.php">
            <i class="fa fa-envelope me-1"></i>Contact
          </a>
        </li>
      </ul>
      <a href="<?= $base ?>/order.php" class="btn btn-light btn-sm ms-3 fw-semibold">
        <i class="fa fa-gift me-1"></i>Send Flowers
      </a>
    </div>
  </div>
</nav>

<!-- ── Flash message ── -->
<div class="container mt-3">
  <?= renderFlash() ?>
</div>
