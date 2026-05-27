<?php
/**
 * admin/orders/delete.php — Delete order with confirmation
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, order_code, customer_name FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    setFlash('danger', 'Order not found.');
    header("Location: $adminBase/orders/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $conn->begin_transaction();
    try {
        $conn->execute_query("DELETE FROM status_history WHERE order_id = ?", [$id]);
        $conn->execute_query("DELETE FROM uploaded_files WHERE order_id = ?", [$id]);
        $conn->execute_query("DELETE FROM order_items WHERE order_id = ?", [$id]);
        $conn->execute_query("DELETE FROM orders WHERE id = ?", [$id]);
        $conn->commit();
        setFlash('success', "Order {$order['order_code']} deleted.");
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Delete failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    header("Location: $adminBase/orders/index.php");
    exit;
}

$adminPageTitle = 'Delete Order';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1 class="text-danger"><i class="fa fa-trash me-2"></i>Delete Order</h1>
</div>

<div class="admin-form-card" style="max-width:540px">
  <div class="text-center mb-4">
    <div style="width:72px;height:72px;background:#ffebee;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
    </div>
    <h5 class="fw-bold">Are you absolutely sure?</h5>
    <p class="text-muted">
      You are about to permanently delete order
      <strong><?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></strong>
      for <strong><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>.
      <br>All items, status history and attachments for this order will also be deleted.
      <strong class="text-danger">This action cannot be undone.</strong>
    </p>
  </div>
  <form method="post" class="d-flex gap-2 justify-content-center">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <a href="<?= $adminBase ?>/orders/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
      <i class="fa fa-arrow-left me-1"></i>Cancel
    </a>
    <button type="submit" class="btn btn-danger">
      <i class="fa fa-trash me-1"></i>Yes, Delete Order
    </button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
