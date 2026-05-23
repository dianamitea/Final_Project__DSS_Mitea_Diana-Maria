<?php
/**
 * admin/products/edit.php — Edit product (A5, A8)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminBase  = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn       = getDbConnection();
$id         = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$pStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$pStmt->bind_param('i', $id);
$pStmt->execute();
$prod = $pStmt->get_result()->fetch_assoc();
if (!$prod) {
    setFlash('danger', 'Product not found.');
    header("Location: $adminBase/products/index.php");
    exit;
}

$adminPageTitle = 'Edit: ' . $prod['name'];
$categories     = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$errors         = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['_general'] = 'Security check failed.';
    } else {
        $name     = trim($_POST['name']            ?? '');
        $desc     = trim($_POST['description']     ?? '');
        $price    = $_POST['price']                ?? '';
        $stock    = (int)($_POST['stock_quantity'] ?? 0);
        $catId    = (int)($_POST['category_id']    ?? 0);
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active   = isset($_POST['is_active'])   ? 1 : 0;

        if (strlen($name) < 2)                 $errors['name']       = 'Name required.';
        if (!is_numeric($price)||(float)$price<=0) $errors['price']  = 'Valid price required.';
        if ($stock < 0)                        $errors['stock']      = 'Stock cannot be negative.';
        if ($catId <= 0)                       $errors['category_id']= 'Category required.';

        // Image upload
        $imagePath = $prod['image_path'];
        if (!empty($_FILES['image']['name'])) {
            $file    = $_FILES['image'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['image'] = 'Upload error.';
            } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) {
                $errors['image'] = 'Only JPEG/PNG/GIF/WEBP allowed.';
            } elseif ($file['size'] > 5*1024*1024) {
                $errors['image'] = 'Max 5MB.';
            } else {
                $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newName   = uniqid('prod_') . '.' . $ext;
                $uploadDir = dirname(__DIR__, 2) . '/assets/uploads/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $imagePath = 'assets/uploads/products/' . $newName;
                } else {
                    $errors['image'] = 'Failed to save file.';
                }
            }
        }

        if (empty($errors)) {
            $u = $conn->prepare(
                "UPDATE products SET name=?, description=?, price=?, stock_quantity=?,
                  category_id=?, image_path=?, is_featured=?, is_active=? WHERE id=?"
            );
            $priceF = (float)$price;
            $u->bind_param('ssdiiisii', $name, $desc, $priceF, $stock, $catId, $imagePath, $featured, $active, $id);
            if ($u->execute()) {
                setFlash('success', 'Product updated.');
                header("Location: $adminBase/products/index.php");
                exit;
            } else {
                $errors['_general'] = 'Database error.';
            }
        }
        // Merge for redisplay
        $prod = array_merge($prod, ['name'=>$name,'description'=>$desc,'price'=>$price,
            'stock_quantity'=>$stock,'category_id'=>$catId,'is_featured'=>$featured,'is_active'=>$active]);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-edit me-2 text-primary-custom"></i>Edit Product</h1>
  <a href="<?= $adminBase ?>/products/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-arrow-left me-1"></i>Back
  </a>
</div>

<?php if (!empty($errors['_general'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($errors['_general'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-form-card">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars($prod['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price (RON) *</label>
            <input type="number" name="price" step="0.01" min="0"
                   class="form-control <?= isset($errors['price'])?'is-invalid':'' ?>"
                   value="<?= htmlspecialchars($prod['price'], ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['price'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock Quantity *</label>
            <input type="number" name="stock_quantity" min="0"
                   class="form-control <?= isset($errors['stock'])?'is-invalid':'' ?>"
                   value="<?= (int)$prod['stock_quantity'] ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Category *</label>
            <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':'' ?>" required>
              <option value="">-- Select --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)$prod['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
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
        <?php if ($prod['image_path']): ?>
          <img src="/Final_Project__DSS_Mitea_Diana-Maria/<?= htmlspecialchars($prod['image_path'], ENT_QUOTES, 'UTF-8') ?>"
               class="img-fluid rounded mb-2" style="max-height:150px" alt="">
          <p class="small text-muted">Upload new image to replace</p>
        <?php endif; ?>
        <input type="file" name="image" class="form-control <?= isset($errors['image'])?'is-invalid':'' ?>"
               accept="image/*">
        <?php if (isset($errors['image'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['image'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      </div>
      <div class="admin-form-card mb-4">
        <div class="form-section-title">Settings</div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                 <?= $prod['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="isActive">Active</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1"
                 <?= $prod['is_featured'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="isFeatured">Featured on homepage</label>
        </div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary py-2">
          <i class="fa fa-save me-2"></i>Save Changes
        </button>
      </div>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
