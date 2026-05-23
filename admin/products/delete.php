<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
if (!$prod) {
    setFlash('danger', 'Product not found.');
    header("Location: $adminBase/products/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    // Check for dependent order items
    $check = $conn->prepare("SELECT COUNT(*) AS n FROM order_items WHERE product_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    if ($check->get_result()->fetch_assoc()['n'] > 0) {
        setFlash('warning', 'Cannot delete — product is referenced by existing orders. Deactivate it instead.');
    } else {
        $conn->prepare("DELETE FROM products WHERE id = ?")->execute_query([$id]);
        setFlash('success', "Product \"{$prod['name']}\" deleted.");
    }
    header("Location: $adminBase/products/index.php");
    exit;
}

$adminPageTitle = 'Delete Product';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1 class="text-danger"><i class="fa fa-trash me-2"></i>Delete Product</h1>
</div>

<div class="admin-form-card" style="max-width:500px">
  <div class="text-center mb-4">
    <div style="width:64px;height:64px;background:#ffebee;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
      <i class="fa fa-exclamation-triangle fa-xl text-danger"></i>
    </div>
    <h5 class="fw-bold">Delete "<?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?>"?</h5>
    <p class="text-muted">This product will be permanently removed. This action cannot be undone.</p>
  </div>
  <form method="post" class="d-flex gap-2 justify-content-center">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <a href="<?= $adminBase ?>/products/index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-danger">
      <i class="fa fa-trash me-1"></i>Delete
    </button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
