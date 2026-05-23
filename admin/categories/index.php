<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Categories';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

$categories = $conn->query(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.sort_order, c.name"
)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-tags me-2 text-primary-custom"></i>Categories</h1>
  <a href="<?= $adminBase ?>/categories/create.php" class="btn btn-primary">
    <i class="fa fa-plus me-1"></i>Add Category
  </a>
</div>

<div class="admin-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Description</th>
          <th class="text-center">Products</th>
          <th class="text-center">Order</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted small"><?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="small text-muted">
            <?= htmlspecialchars(mb_substr($c['description'] ?? '', 0, 60), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="text-center">
            <span class="badge bg-light text-dark border"><?= (int)$c['product_count'] ?></span>
          </td>
          <td class="text-center"><?= (int)$c['sort_order'] ?></td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= $adminBase ?>/categories/edit.php?id=<?= (int)$c['id'] ?>"
                 class="btn btn-sm btn-outline-primary" title="Edit">
                <i class="fa fa-edit"></i>
              </a>
              <a href="<?= $adminBase ?>/categories/delete.php?id=<?= (int)$c['id'] ?>"
                 class="btn btn-sm btn-outline-danger" title="Delete"
                 onclick="return confirm('Delete this category?')">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
