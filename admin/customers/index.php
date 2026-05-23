<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Customers';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

$search = trim($_GET['search'] ?? '');
$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $like   = '%' . $search . '%';
    $where  = "WHERE CONCAT(c.first_name,' ',c.last_name) LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$stmt = $conn->prepare(
    "SELECT c.*, CONCAT(c.first_name,' ',c.last_name) AS full_name, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_price),0) AS total_spent
     FROM customers c LEFT JOIN orders o ON o.customer_email = c.email
     $where GROUP BY c.id ORDER BY c.created_at DESC"
);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-users me-2 text-primary-custom"></i>Customers</h1>
</div>

<div class="filters-bar">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label small fw-semibold mb-1">Search</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Name, email or phone…"
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa fa-search"></i>
      </button>
      <a href="<?= $adminBase ?>/customers/index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
    </div>
  </form>
</div>

<div class="admin-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th class="text-center">Orders</th>
          <th class="text-end">Total Spent</th>
          <th>Joined</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php while ($c = $customers->fetch_assoc()): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted small"><?= htmlspecialchars($c['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-center">
            <span class="badge bg-light text-dark border"><?= (int)$c['order_count'] ?></span>
          </td>
          <td class="text-end fw-semibold"><?= number_format((float)$c['total_spent'], 2) ?> RON</td>
          <td class="small text-muted">
            <?= htmlspecialchars(date('d M Y', strtotime($c['created_at'])), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td>
            <a href="<?= $adminBase ?>/customers/view.php?id=<?= (int)$c['id'] ?>"
               class="btn btn-sm btn-outline-secondary">
              <i class="fa fa-eye me-1"></i>View
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
