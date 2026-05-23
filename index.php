<?php
/**
 * index.php — Public home page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Welcome';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
$conn      = getDbConnection();

// Fetch featured categories (active ones)
$cats = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY id LIMIT 6");

// Fetch featured products (newest 8 active products)
$featured = $conn->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.is_active = 1
     ORDER BY p.id DESC
     LIMIT 8"
);

include __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ── -->
<section class="hero-section text-center">
  <div class="container position-relative">
    <p class="text-white-50 mb-2 fw-semibold letter-spacing-wide">
      <i class="fa fa-spa me-2"></i>Fresh · Hand-crafted · Delivered
    </p>
    <h1 class="hero-title mb-3">Flowers for Every<br>Special Moment</h1>
    <p class="hero-subtitle mb-4">From birthdays to weddings — we create bespoke floral arrangements<br>delivered fresh to your door across Romania.</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= $base ?>/products.php" class="btn btn-light btn-lg px-4 fw-semibold">
        <i class="fa fa-leaf me-2"></i>Browse Flowers
      </a>
      <a href="<?= $base ?>/order.php" class="btn btn-outline-light btn-lg px-4">
        <i class="fa fa-shopping-cart me-2"></i>Order Now
      </a>
    </div>
  </div>
</section>

<!-- ── Why us ── -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <div class="p-3">
          <div class="icon-circle mx-auto mb-3"><i class="fa fa-seedling fa-2x text-primary-custom"></i></div>
          <h6 class="fw-semibold">Fresh Daily</h6>
          <p class="text-muted small mb-0">All flowers sourced fresh every morning</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3">
          <div class="icon-circle mx-auto mb-3"><i class="fa fa-truck fa-2x text-primary-custom"></i></div>
          <h6 class="fw-semibold">Same-Day Delivery</h6>
          <p class="text-muted small mb-0">Order before 12:00 for same-day delivery</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3">
          <div class="icon-circle mx-auto mb-3"><i class="fa fa-award fa-2x text-primary-custom"></i></div>
          <h6 class="fw-semibold">Expert Florists</h6>
          <p class="text-muted small mb-0">15+ years of floral artistry experience</p>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3">
          <div class="icon-circle mx-auto mb-3"><i class="fa fa-heart fa-2x text-primary-custom"></i></div>
          <h6 class="fw-semibold">Made with Love</h6>
          <p class="text-muted small mb-0">Every bouquet crafted with care and passion</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Categories ── -->
<section class="py-5" id="categories">
  <div class="container">
    <h2 class="section-heading mb-4">Shop by Category</h2>
    <div class="row g-3">
      <?php while ($cat = $cats->fetch_assoc()): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <a href="<?= $base ?>/products.php?category_id=<?= (int)$cat['id'] ?>"
           class="card text-center p-3 h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none category-card">
          <i class="fa fa-spa fa-2x text-primary-custom mb-2"></i>
          <div class="fw-semibold small"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></div>
        </a>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ── Featured Products ── -->
<section class="py-5 bg-white" id="featured">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <h2 class="section-heading mb-0">Featured Arrangements</h2>
      <a href="<?= $base ?>/products.php" class="btn btn-outline-primary btn-sm">View All</a>
    </div>
    <div class="row g-4">
      <?php while ($p = $featured->fetch_assoc()): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card product-card h-100">
          <?php if ($p['image_path']): ?>
            <img src="<?= $base . '/' . htmlspecialchars($p['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                 class="card-img-top" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>">
          <?php else: ?>
            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                 style="height:220px; border-radius:12px 12px 0 0;">
              <i class="fa fa-spa fa-4x text-muted opacity-50"></i>
            </div>
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <span class="badge bg-light text-muted small mb-1"><?= htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <h6 class="card-title fw-semibold mb-1"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></h6>
            <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
              <span class="price-badge"><?= number_format((float)$p['price'], 2) ?> RON</span>
              <a href="<?= $base ?>/product.php?id=<?= (int)$p['id'] ?>"
                 class="btn btn-sm btn-outline-primary">View</a>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ── CTA Banner ── -->
<section class="py-5" style="background: linear-gradient(135deg,#880e4f,#c2185b);">
  <div class="container text-center text-white">
    <h3 class="fw-bold mb-2">Have a special occasion coming up?</h3>
    <p class="mb-4 opacity-75">Let us craft the perfect floral arrangement for your moment.</p>
    <a href="<?= $base ?>/order.php" class="btn btn-light btn-lg px-5 fw-semibold">
      <i class="fa fa-gift me-2"></i>Place an Order
    </a>
  </div>
</section>

<style>
.icon-circle { width:64px; height:64px; background:var(--primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; }
.category-card { border: 2px solid #f0d0e0; color:var(--text-dark); border-radius:12px; transition: all .25s; }
.category-card:hover { border-color:var(--primary); transform:translateY(-3px); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
