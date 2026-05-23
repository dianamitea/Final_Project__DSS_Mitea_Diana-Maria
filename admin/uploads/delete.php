<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM uploaded_files WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if (!$file) {
    setFlash('danger', 'File not found.');
    header("Location: $adminBase/uploads/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    // Delete physical file
    $fullPath = dirname(__DIR__, 2) . '/' . $file['file_path'];
    if (file_exists($fullPath)) @unlink($fullPath);

    $conn->prepare("DELETE FROM uploaded_files WHERE id = ?")->execute_query([$id]);
    setFlash('success', 'File deleted.');
    header("Location: $adminBase/uploads/index.php");
    exit;
}

$adminPageTitle = 'Delete File';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1 class="text-danger"><i class="fa fa-trash me-2"></i>Delete File</h1>
</div>

<div class="admin-form-card" style="max-width:480px">
  <div class="text-center mb-4">
    <div style="width:64px;height:64px;background:#ffebee;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
      <i class="fa fa-exclamation-triangle fa-xl text-danger"></i>
    </div>
    <h5 class="fw-bold">Delete "<?= htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8') ?>"?</h5>
    <p class="text-muted">The file will be permanently removed from the server and database.</p>
  </div>
  <form method="post" class="d-flex gap-2 justify-content-center">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <a href="<?= $adminBase ?>/uploads/index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-danger"><i class="fa fa-trash me-1"></i>Delete</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
