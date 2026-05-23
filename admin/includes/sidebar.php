<?php
/**
 * Admin sidebar navigation
 */
$adminBase   = $adminBase   ?? '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$currentPath = $_SERVER['PHP_SELF'] ?? '';

function isActive(string $segment): string {
    global $currentPath;
    return str_contains($currentPath, $segment) ? 'active' : '';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main</div>
    <a href="<?= $adminBase ?>/index.php" class="sidebar-link <?= isActive('/admin/index') ?>">
      <i class="fa fa-tachometer-alt"></i><span>Dashboard</span>
    </a>

    <div class="sidebar-section-label">Orders</div>
    <a href="<?= $adminBase ?>/orders/index.php" class="sidebar-link <?= isActive('/orders/') ?>">
      <i class="fa fa-shopping-bag"></i><span>Manage Orders</span>
    </a>
    <a href="<?= $adminBase ?>/orders/create.php" class="sidebar-link <?= isActive('/orders/create') ?>">
      <i class="fa fa-plus-circle"></i><span>New Order</span>
    </a>

    <div class="sidebar-section-label">Catalogue</div>
    <a href="<?= $adminBase ?>/products/index.php" class="sidebar-link <?= isActive('/products/') ?>">
      <i class="fa fa-leaf"></i><span>Products</span>
    </a>
    <a href="<?= $adminBase ?>/categories/index.php" class="sidebar-link <?= isActive('/categories/') ?>">
      <i class="fa fa-tags"></i><span>Categories</span>
    </a>

    <div class="sidebar-section-label">Customers</div>
    <a href="<?= $adminBase ?>/customers/index.php" class="sidebar-link <?= isActive('/customers/') ?>">
      <i class="fa fa-users"></i><span>Customers</span>
    </a>

    <div class="sidebar-section-label">Business Tools</div>
    <a href="<?= $adminBase ?>/reports/index.php" class="sidebar-link <?= isActive('/reports/') ?>">
      <i class="fa fa-chart-bar"></i><span>Reports &amp; Analytics</span>
    </a>
    <a href="<?= $adminBase ?>/uploads/index.php" class="sidebar-link <?= isActive('/uploads/') ?>">
      <i class="fa fa-file-upload"></i><span>File Uploads</span>
    </a>
    <a href="<?= $adminBase ?>/currency.php" class="sidebar-link <?= isActive('/currency') ?>">
      <i class="fa fa-exchange-alt"></i><span>Currency Rates</span>
    </a>
  </nav>
</aside>
