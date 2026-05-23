<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Add Category';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();
$errors         = [];
$form           = ['name'=>'','description'=>'','sort_order'=>0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $form['name']        = trim($_POST['name']        ?? '');
        $form['description'] = trim($_POST['description'] ?? '');
        $form['sort_order']  = (int)($_POST['sort_order'] ?? 0);

        if (strlen($form['name']) < 2) $errors['name'] = 'Name is required.';

        if (empty($errors)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $form['name']));
            // Ensure unique slug
            $existing = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
            $existing->bind_param('s', $slug);
            $existing->execute();
            if ($existing->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }

            $conn->prepare(
                "INSERT INTO categories (name, slug, description, sort_order) VALUES (?,?,?,?)"
            )->execute_query([$form['name'], $slug, $form['description'], $form['sort_order']]);

            setFlash('success', 'Category "' . htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') . '" created.');
            header("Location: $adminBase/categories/index.php");
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-plus me-2 text-primary-custom"></i>Add Category</h1>
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
    <div class="mb-3">
      <label class="form-label">Category Name *</label>
      <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
             value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>" required>
      <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <div class="mb-4">
      <label class="form-label">Sort Order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= (int)$form['sort_order'] ?>" min="0" style="max-width:120px">
      <small class="text-muted">Lower numbers appear first (0 = top)</small>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save Category</button>
      <a href="<?= $adminBase ?>/categories/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
