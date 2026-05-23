<?php
/**
 * Admin shared header.
 * Set $adminPageTitle before including.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = $adminPageTitle ?? 'Admin Panel';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$publicBase     = '/Final_Project__DSS_Mitea_Diana-Maria';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($adminPageTitle, ENT_QUOTES, 'UTF-8') ?> | Petals &amp; Bloom Admin</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Admin CSS -->
  <link href="<?= $publicBase ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<!-- ── Top Navbar ── -->
<nav class="navbar navbar-dark admin-topnav px-3 d-flex justify-content-between align-items-center">
  <button class="btn btn-link text-white p-0 d-lg-none" id="sidebarToggle">
    <i class="fa fa-bars fa-lg"></i>
  </button>
  <a class="navbar-brand fw-bold mb-0" href="<?= $adminBase ?>/index.php">
    <i class="fa fa-spa me-2"></i>Petals &amp; Bloom
    <small class="opacity-60 fw-normal ms-2 d-none d-sm-inline" style="font-size:.7rem;">ADMIN</small>
  </a>
  <div class="d-flex align-items-center gap-3">
    <a href="<?= $publicBase ?>/index.php" target="_blank" class="text-white-50 small text-decoration-none">
      <i class="fa fa-external-link-alt me-1"></i>View Site
    </a>
    <span class="text-white-50 small">
      <i class="fa fa-user-shield me-1"></i>
      <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
    </span>
    <a href="<?= $adminBase ?>/logout.php" class="btn btn-outline-light btn-sm">
      <i class="fa fa-sign-out-alt me-1"></i>Logout
    </a>
  </div>
</nav>

<div class="admin-layout">
  <!-- ── Sidebar ── -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- ── Main content area ── -->
  <main class="admin-main">
    <!-- Breadcrumb / flash -->
    <div class="admin-content-header px-4 py-3 border-bottom">
      <?= renderFlash() ?>
    </div>
    <div class="admin-content px-4 py-4">
