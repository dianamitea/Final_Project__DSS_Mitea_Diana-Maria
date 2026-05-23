<?php
/**
 * admin/products/index.php — Products list (A5)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Products';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

$search  = trim($_GET['search']   ?? '');
$catId   = (int)($_GET['category'] ?? 0);
$stock   = $_GET['stock']          ?? '';

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $like     = '%' . $search . '%';
    $where[]  = "(p.name LIKE ? OR p.description LIKE ?)";
    $params   = array_merge($params, [$like, $like]);
    $types   .= 'ss';
}
if ($catId > 0) {
    $where[]  = "p.category_id = ?";
    $params[] = $catId;
    $types   .= 'i';
}
if ($stock === 'low') {
    $where[] = "p.stock_quantity <= 5";
} elseif ($stock === 'out') {
    $where[] = "p.stock_quantity = 0";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $conn->prepare(
    "SELECT p.*, c.name AS cat_name FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     $whereSQL ORDER BY p.name"
);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-leaf me-2 text-primary-custom"></i>Products</h1>
  <a href="<?= $adminBase ?>/products/create.php" class="btn btn-primary">
    <i class="fa fa-plus me-1"></i>Add Product
  </a>
</div>

<div class="filters-bar">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small fw-semibold mb-1">Search</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Name or description…"
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Category</label>
      <select name="category" class="form-select form-select-sm">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catId === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">Stock</label>
      <select name="stock" class="form-select form-select-sm">
        <option value="">All</option>
        <option value="low"  <?= $stock === 'low'  ? 'selected' : '' ?>>Low (≤5)</option>
        <option value="out"  <?= $stock === 'out'  ? 'selected' : '' ?>>Out of stock</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-1">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa fa-filter me-1"></i>Filter
      </button>
      <a href="<?= $adminBase ?>/products/index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
    </div>
  </form>
</div>

<div class="admin-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th class="text-end">Price</th>
          <th class="text-center">Stock</th>
          <th class="text-center">Status</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = $products->fetch_assoc()): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <small class="text-muted">
              <?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 60), ENT_QUOTES, 'UTF-8') ?>
              <?= strlen($p['description'] ?? '') > 60 ? '…' : '' ?>
            </small>
          </td>
          <td class="text-muted small"><?= htmlspecialchars($p['cat_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-end fw-semibold"><?= number_format((float)$p['price'], 2) ?> RON</td>
          <td class="text-center">
            <?php $qty = (int)$p['stock_quantity']; ?>
            <span class="badge <?= $qty === 0 ? 'bg-danger' : ($qty <= 5 ? 'bg-warning text-dark' : 'bg-success') ?>">
              <?= $qty ?>
            </span>
          </td>
          <td class="text-center">
            <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
              <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= $adminBase ?>/products/edit.php?id=<?= (int)$p['id'] ?>"
                 class="btn btn-sm btn-outline-primary" title="Edit">
                <i class="fa fa-edit"></i>
              </a>
              <a href="<?= $adminBase ?>/products/delete.php?id=<?= (int)$p['id'] ?>"
                 class="btn btn-sm btn-outline-danger" title="Delete"
                 onclick="return confirm('Delete this product?')">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
