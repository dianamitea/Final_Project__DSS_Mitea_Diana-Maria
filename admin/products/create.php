<?php
/**
 * admin/products/create.php — Add new product with image upload (A5, A8)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Add Product';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();
$categories     = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$form   = ['name'=>'','description'=>'','price'=>'','stock_quantity'=>'10','category_id'=>'','is_featured'=>0,'is_active'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $form = [
            'name'           => trim($_POST['name']             ?? ''),
            'description'    => trim($_POST['description']      ?? ''),
            'price'          => $_POST['price']                 ?? '',
            'stock_quantity' => (int)($_POST['stock_quantity']  ?? 0),
            'category_id'    => (int)($_POST['category_id']     ?? 0),
            'is_featured'    => isset($_POST['is_featured']) ? 1 : 0,
            'is_active'      => isset($_POST['is_active'])   ? 1 : 0,
        ];

        if (strlen($form['name']) < 2)         $errors['name']          = 'Name is required (min 2 chars).';
        if (!is_numeric($form['price']) || (float)$form['price'] <= 0) $errors['price'] = 'Valid price required.';
        if ($form['stock_quantity'] < 0)       $errors['stock_quantity']= 'Stock cannot be negative.';
        if ($form['category_id'] <= 0)         $errors['category_id']   = 'Category is required.';

        // Image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $file    = $_FILES['image'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $maxSize = 5 * 1024 * 1024;
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['image'] = 'Upload error code ' . $file['error'];
            } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) {
                $errors['image'] = 'Only JPEG, PNG, GIF, WEBP allowed.';
            } elseif ($file['size'] > $maxSize) {
                $errors['image'] = 'File exceeds 5MB limit.';
            } else {
                $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newName    = uniqid('prod_') . '.' . $ext;
                $uploadDir  = dirname(__DIR__, 2) . '/assets/uploads/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $imagePath = 'assets/uploads/products/' . $newName;
                } else {
                    $errors['image'] = 'Failed to save image.';
                }
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare(
                "INSERT INTO products (name, description, price, stock_quantity, category_id, image_path, is_featured, is_active)
                 VALUES (?,?,?,?,?,?,?,?)"
            );
            $price = (float)$form['price'];
            $stmt->bind_param('ssdiiisi',
                $form['name'], $form['description'], $price, $form['stock_quantity'],
                $form['category_id'], $imagePath, $form['is_featured'], $form['is_active']
            );
            if ($stmt->execute()) {
                setFlash('success', 'Product "' . htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') . '" created.');
                header("Location: $adminBase/products/index.php");
                exit;
            } else {
                $errors['_general'] = 'Database error.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-plus me-2 text-primary-custom"></i>Add Product</h1>
  <a href="<?= $adminBase ?>/products/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back
  </a>
</div>

<?php if (!empty($errors['_general'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-form-card">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price (RON) *</label>
            <input type="number" name="price" step="0.01" min="0"
                   class="form-control <?= isset($errors['price'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($form['price'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['price'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock Quantity *</label>
            <input type="number" name="stock_quantity" min="0"
                   class="form-control <?= isset($errors['stock_quantity'])?'is-invalid':'' ?>"
                   value="<?= (int)$form['stock_quantity'] ?>" required>
            <?php if (isset($errors['stock_quantity'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['stock_quantity'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Category *</label>
            <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':'' ?>" required>
              <option value="">-- Select --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)$form['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['category_id'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Product Image</div>
        <input type="file" name="image" class="form-control <?= isset($errors['image'])?'is-invalid':'' ?>"
               accept="image/*" id="imageInput">
        <?php if (isset($errors['image'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['image'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <small class="text-muted mt-1 d-block">JPEG/PNG/GIF/WEBP, max 5MB</small>
        <div id="imagePreview" class="mt-2" style="display:none">
          <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height:180px">
        </div>
      </div>
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Settings</div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                 <?= $form['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="isActive">Active (visible to customers)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1"
                 <?= $form['is_featured'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="isFeatured">Featured on homepage</label>
        </div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary py-2">
          <i class="fa fa-save me-2"></i>Save Product
        </button>
      </div>
    </div>
  </div>
</form>

<script>
$('#imageInput').on('change', function () {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      $('#previewImg').attr('src', e.target.result);
      $('#imagePreview').show();
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
