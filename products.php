<?php
/**
 * products.php — Public product listing with GET-based category filter (P2).
 * GET params: ?category_id=N  and optional ?search=term
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Our Flowers';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

// ── Validate GET params safely ──
$categoryId = null;
if (isset($_GET['category_id']) && ctype_digit((string)$_GET['category_id'])) {
    $categoryId = (int)$_GET['category_id'];
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ── Fetch categories ──
$cats = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// ── Verify category exists (if provided) ──
$activeCat = null;
if ($categoryId !== null) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $activeCat = $stmt->get_result()->fetch_assoc();
    if (!$activeCat) {
        // Invalid category – reset
        $categoryId = null;
    }
}

// ── Build product query ──
$sql    = "SELECT p.*, c.name AS category_name
           FROM products p
           JOIN categories c ON p.category_id = c.id
           WHERE p.is_active = 1";
$params = [];
$types  = '';

if ($categoryId !== null) {
    $sql    .= " AND p.category_id = ?";
    $params[] = $categoryId;
    $types   .= 'i';
}
if ($search !== '') {
    $sql    .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container">
    <h1 class="fw-bold text-primary-custom mb-1">
      <?php if ($activeCat): ?>
        <?= htmlspecialchars($activeCat['name'], ENT_QUOTES, 'UTF-8') ?>
      <?php else: ?>
        All Flower Arrangements
      <?php endif; ?>
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $base ?>/index.php">Home</a></li>
        <li class="breadcrumb-item active">Flowers</li>
        <?php if ($activeCat): ?>
          <li class="breadcrumb-item active"><?= htmlspecialchars($activeCat['name'], ENT_QUOTES, 'UTF-8') ?></li>
        <?php endif; ?>
      </ol>
    </nav>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <!-- Sidebar filters -->
      <div class="col-lg-3">
        <!-- Search -->
        <div class="card mb-3 p-3">
          <h6 class="fw-semibold mb-2"><i class="fa fa-search me-2 text-primary-custom"></i>Search</h6>
          <form method="get" action="">
            <?php if ($categoryId): ?>
              <input type="hidden" name="category_id" value="<?= (int)$categoryId ?>">
            <?php endif; ?>
            <div class="input-group">
              <input type="text" name="search" class="form-control form-control-sm"
                     placeholder="Search flowers…"
                     value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
              <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-search"></i></button>
            </div>
          </form>
        </div>

        <!-- Categories -->
        <div class="card p-3">
          <h6 class="fw-semibold mb-3"><i class="fa fa-tags me-2 text-primary-custom"></i>Categories</h6>
          <a href="<?= $base ?>/products.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
             class="category-pill d-block mb-2 <?= $categoryId === null ? 'active' : '' ?>">
            All Flowers
          </a>
          <?php while ($c = $cats->fetch_assoc()): ?>
            <a href="<?= $base ?>/products.php?category_id=<?= (int)$c['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
               class="category-pill d-block mb-2 <?= (int)$c['id'] === $categoryId ? 'active' : '' ?>">
              <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Product grid -->
      <div class="col-lg-9">
        <?php if ($search !== ''): ?>
          <p class="text-muted mb-3">Search results for: <strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php endif; ?>

        <div class="row g-4" id="productGrid">
          <?php
          $count = 0;
          while ($p = $products->fetch_assoc()):
            $count++;
          ?>
          <div class="col-6 col-md-4">
            <div class="card product-card h-100">
              <?php if ($p['image_path']): ?>
                <img src="<?= $base . '/' . htmlspecialchars($p['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                     class="card-img-top" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>">
              <?php else: ?>
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                     style="height:200px; border-radius:12px 12px 0 0;">
                  <i class="fa fa-spa fa-3x text-muted opacity-50"></i>
                </div>
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <span class="badge bg-light text-muted small mb-1">
                  <?= htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <h6 class="card-title fw-semibold mb-1">
                  <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                </h6>
                <p class="text-muted small mb-2 flex-grow-1">
                  <?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 70), ENT_QUOTES, 'UTF-8') ?>…
                </p>
                <?php if ((int)$p['stock_quantity'] <= 5): ?>
                  <small class="stock-low mb-1">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    Only <?= (int)$p['stock_quantity'] ?> left!
                  </small>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                  <span class="price-badge"><?= number_format((float)$p['price'], 2) ?> RON</span>
                  <div class="d-flex gap-1">
                    <a href="<?= $base ?>/product.php?id=<?= (int)$p['id'] ?>"
                       class="btn btn-sm btn-outline-primary">Details</a>
                    <a href="<?= $base ?>/order.php?product_id=<?= (int)$p['id'] ?>"
                       class="btn btn-sm btn-primary">Order</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endwhile; ?>

          <?php if ($count === 0): ?>
          <div class="col-12">
            <div class="text-center py-5">
              <i class="fa fa-search fa-3x text-muted opacity-50 mb-3"></i>
              <h5 class="text-muted">No products found.</h5>
              <a href="<?= $base ?>/products.php" class="btn btn-primary mt-2">View All Flowers</a>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
