<?php
/**
 * contact.php — Public contact page
 */
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Contact Us';
$base      = '/Final_Project__DSS_Mitea_Diana-Maria';
include __DIR__ . '/includes/header.php';
?>

<section class="py-4" style="background:var(--primary-light);">
  <div class="container">
    <h1 class="fw-bold text-primary-custom mb-1">Contact Us</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $base ?>/index.php">Home</a></li>
        <li class="breadcrumb-item active">Contact</li>
      </ol>
    </nav>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-5">
      <div class="col-md-5">
        <h4 class="fw-bold mb-4">Get in Touch</h4>
        <ul class="list-unstyled">
          <li class="d-flex gap-3 mb-3">
            <div class="icon-circle flex-shrink-0"><i class="fa fa-map-marker-alt text-primary-custom"></i></div>
            <div><strong>Address</strong><br><span class="text-muted">Str. Florilor nr. 1, Sector 1, Bucharest</span></div>
          </li>
          <li class="d-flex gap-3 mb-3">
            <div class="icon-circle flex-shrink-0"><i class="fa fa-phone text-primary-custom"></i></div>
            <div><strong>Phone</strong><br><span class="text-muted">+40 721 000 111</span></div>
          </li>
          <li class="d-flex gap-3 mb-3">
            <div class="icon-circle flex-shrink-0"><i class="fa fa-envelope text-primary-custom"></i></div>
            <div><strong>Email</strong><br><span class="text-muted">hello@petalsandbloom.ro</span></div>
          </li>
          <li class="d-flex gap-3 mb-3">
            <div class="icon-circle flex-shrink-0"><i class="fa fa-clock text-primary-custom"></i></div>
            <div><strong>Working Hours</strong><br><span class="text-muted">Monday–Saturday: 08:00–20:00<br>Sunday: 09:00–18:00</span></div>
          </li>
        </ul>
      </div>
      <div class="col-md-7">
        <div class="card p-4 shadow-sm">
          <h5 class="fw-semibold mb-3">Send us a Message</h5>
          <div class="alert alert-info small">
            <i class="fa fa-info-circle me-2"></i>
            For orders, please use the <a href="<?= $base ?>/order.php">Order page</a>.
            This form is for general enquiries only.
          </div>
          <form>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Your Name</label>
                <input type="text" class="form-control" placeholder="Full name">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="email@example.com">
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control" placeholder="How can we help?">
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea class="form-control" rows="5" placeholder="Your message…"></textarea>
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">
              <i class="fa fa-paper-plane me-2"></i>Send Message
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.icon-circle { width:44px; height:44px; background:var(--primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
