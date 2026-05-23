<?php
/**
 * product.php — Single product detail page (GET ?id=N)  (P2 GET feature)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$base = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn = getDbConnection();

// ── Validate ID ──
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: ' . $base . '/products.php');
    exit;
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare(
    "SELECT p.*, c.name AS category_name, c.id AS cat_id
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.id = ? AND p.is_active = 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    // Product not found – redirect safely
    header('Location: ' . $base . '/products.php');
    exit;
}

// Related products (same category, different ID)
$rel = $conn->prepare(
    "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY id DESC LIMIT 4"
);
$rel->bind_param('ii', $product['cat_id'], $id);
$rel->execute();
$related = $rel->get_result();

$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $base ?>/index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= $base ?>/products.php">Flowers</a></li>
        <li class="breadcrumb-item">
          <a href="<?= $base ?>/products.php?category_id=<?= (int)$product['cat_id'] ?>">
            <?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></li>
      </ol>
    </nav>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-5 align-items-start">
      <!-- Image -->
      <div class="col-md-5">
        <?php if ($product['image_path']): ?>
          <img src="<?= $base . '/' . htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8') ?>"
               class="img-fluid rounded-custom shadow" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
          <div class="d-flex align-items-center justify-content-center bg-light rounded-custom shadow"
               style="height:380px;">
            <i class="fa fa-spa fa-6x text-muted opacity-40"></i>
          </div>
        <?php endif; ?>
      </div>

      <!-- Details -->
      <div class="col-md-7">
        <span class="badge text-bg-light text-primary-custom mb-2">
          <?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <h1 class="fw-bold mb-2"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="fs-3 fw-bold text-primary-custom mb-3">
          <?= number_format((float)$product['price'], 2) ?> RON
        </p>
        <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>

        <div class="mb-3">
          <?php if ((int)$product['stock_quantity'] > 5): ?>
            <span class="badge bg-success-subtle text-success fs-6 px-3 py-2">
              <i class="fa fa-check-circle me-1"></i> In Stock (<?= (int)$product['stock_quantity'] ?> available)
            </span>
          <?php elseif ((int)$product['stock_quantity'] > 0): ?>
            <span class="badge bg-warning-subtle text-warning fs-6 px-3 py-2">
              <i class="fa fa-exclamation-circle me-1"></i>
              Low Stock — only <?= (int)$product['stock_quantity'] ?> left!
            </span>
          <?php else: ?>
            <span class="badge bg-danger-subtle text-danger fs-6 px-3 py-2">
              <i class="fa fa-times-circle me-1"></i> Out of Stock
            </span>
          <?php endif; ?>
        </div>

        <?php if ((int)$product['stock_quantity'] > 0): ?>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= $base ?>/order.php?product_id=<?= (int)$product['id'] ?>"
             class="btn btn-primary btn-lg px-4">
            <i class="fa fa-shopping-cart me-2"></i>Order This Arrangement
          </a>
          <a href="<?= $base ?>/products.php?category_id=<?= (int)$product['cat_id'] ?>"
             class="btn btn-outline-primary btn-lg">
            <i class="fa fa-arrow-left me-2"></i>More Like This
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Related products -->
    <?php if ($related->num_rows > 0): ?>
    <hr class="my-5">
    <h4 class="section-heading mb-4">You Might Also Like</h4>
    <div class="row g-4">
      <?php while ($r = $related->fetch_assoc()): ?>
      <div class="col-6 col-md-3">
        <div class="card product-card h-100">
          <?php if ($r['image_path']): ?>
            <img src="<?= $base . '/' . htmlspecialchars($r['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                 class="card-img-top" alt="<?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?>">
          <?php else: ?>
            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                 style="height:180px; border-radius:12px 12px 0 0;">
              <i class="fa fa-spa fa-3x text-muted opacity-50"></i>
            </div>
          <?php endif; ?>
          <div class="card-body">
            <h6 class="fw-semibold small mb-1"><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></h6>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <span class="price-badge"><?= number_format((float)$r['price'], 2) ?> RON</span>
              <a href="<?= $base ?>/product.php?id=<?= (int)$r['id'] ?>"
                 class="btn btn-sm btn-outline-primary">View</a>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
