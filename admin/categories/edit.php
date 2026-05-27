<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn      = getDbConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
if (!$cat) {
    setFlash('danger', 'Category not found.');
    header("Location: $adminBase/categories/index.php");
    exit;
}

$adminPageTitle = 'Edit Category';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $name      = trim($_POST['name']        ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (strlen($name) < 2) $errors['name'] = 'Name required.';

        if (empty($errors)) {
            $conn->execute_query(
                "UPDATE categories SET name=?, description=?, sort_order=? WHERE id=?",
                [$name, $desc, $sortOrder, $id]
            );
            setFlash('success', 'Category updated.');
            header("Location: $adminBase/categories/index.php");
            exit;
        }
        $cat['name'] = $name; $cat['description'] = $desc; $cat['sort_order'] = $sortOrder;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-edit me-2 text-primary-custom"></i>Edit Category</h1>
  <a href="<?= $adminBase ?>/categories/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back
  </a>
</div>

<?php if (!empty($errors['_general'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="admin-form-card" style="max-width:600px">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="mb-3">
      <label class="form-label">Category Name *</label>
      <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
             value="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>" required>
      <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <div class="mb-4">
      <label class="form-label">Sort Order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= (int)$cat['sort_order'] ?>" min="0" style="max-width:120px">
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save Changes</button>
      <a href="<?= $adminBase ?>/categories/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
