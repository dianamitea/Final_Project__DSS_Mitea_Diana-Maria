<?php
/**
 * Public site shared footer.
 */
$base = $base ?? '/Final_Project__DSS_Mitea_Diana-Maria';
?>

<!-- ── Footer ── -->
<footer class="footer-custom mt-5 pt-5 pb-3">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5 class="fw-bold mb-3"><i class="fa fa-spa me-2"></i>Petals &amp; Bloom</h5>
        <p class="text-muted small">Your local flower shop offering fresh, hand-crafted bouquets and floral arrangements for every occasion. Delivering happiness, one petal at a time.</p>
        <div class="social-icons mt-3">
          <a href="#" class="me-2 text-muted"><i class="fab fa-facebook-f fa-lg"></i></a>
          <a href="#" class="me-2 text-muted"><i class="fab fa-instagram fa-lg"></i></a>
          <a href="#" class="me-2 text-muted"><i class="fab fa-pinterest fa-lg"></i></a>
        </div>
      </div>
      <div class="col-md-2">
        <h6 class="fw-semibold mb-3">Quick Links</h6>
        <ul class="list-unstyled small">
          <li class="mb-1"><a href="<?= $base ?>/index.php" class="text-muted text-decoration-none">Home</a></li>
          <li class="mb-1"><a href="<?= $base ?>/products.php" class="text-muted text-decoration-none">Flowers</a></li>
          <li class="mb-1"><a href="<?= $base ?>/order.php" class="text-muted text-decoration-none">Order</a></li>
          <li class="mb-1"><a href="<?= $base ?>/status.php" class="text-muted text-decoration-none">Track Order</a></li>
          <li class="mb-1"><a href="<?= $base ?>/contact.php" class="text-muted text-decoration-none">Contact</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="fw-semibold mb-3">Contact Us</h6>
        <ul class="list-unstyled small text-muted">
          <li class="mb-2"><i class="fa fa-map-marker-alt me-2 text-primary-custom"></i>Str. Florilor nr. 1, Bucharest</li>
          <li class="mb-2"><i class="fa fa-phone me-2 text-primary-custom"></i>+40 721 000 111</li>
          <li class="mb-2"><i class="fa fa-envelope me-2 text-primary-custom"></i>hello@petalsandbloom.ro</li>
          <li class="mb-2"><i class="fa fa-clock me-2 text-primary-custom"></i>Mon–Sat: 08:00–20:00</li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="fw-semibold mb-3">Occasions We Serve</h6>
        <div class="d-flex flex-wrap gap-1">
          <span class="badge bg-light text-dark">Birthdays</span>
          <span class="badge bg-light text-dark">Weddings</span>
          <span class="badge bg-light text-dark">Anniversaries</span>
          <span class="badge bg-light text-dark">Valentine's</span>
          <span class="badge bg-light text-dark">Mother's Day</span>
          <span class="badge bg-light text-dark">Corporate</span>
          <span class="badge bg-light text-dark">Funerals</span>
        </div>
      </div>
    </div>
    <hr class="mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">&copy; <?= date('Y') ?> Petals &amp; Bloom. All rights reserved.</small>
      <small class="text-muted">DSS Project &mdash; Mitea Diana-Maria</small>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Custom JS -->
<script src="<?= $base ?>/assets/js/main.js"></script>
</body>
</html>
