<?php
/**
 * admin/orders/index.php — List orders with search, filters, sorting (A4, A6)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Orders';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// ── GET filters ──
$search    = trim($_GET['search']      ?? '');
$status    = $_GET['status']           ?? '';
$occasion  = $_GET['occasion']         ?? '';
$dateFrom  = $_GET['date_from']        ?? '';
$dateTo    = $_GET['date_to']          ?? '';
$sort      = in_array($_GET['sort'] ?? '', ['created_at','delivery_date','total_price','status']) ? $_GET['sort'] : 'created_at';
$dir       = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;

// ── Build query ──
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[]  = "(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.customer_phone LIKE ?)";
    $params   = array_merge($params, [$like, $like, $like, $like]);
    $types   .= 'ssss';
}
if ($status !== '') {
    $where[]  = "o.status = ?";
    $params[] = $status;
    $types   .= 's';
}
if ($occasion !== '') {
    $where[]  = "o.occasion = ?";
    $params[] = $occasion;
    $types   .= 's';
}
if ($dateFrom !== '') {
    $where[]  = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
    $types   .= 's';
}
if ($dateTo !== '') {
    $where[]  = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
    $types   .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$cntStmt = $conn->prepare("SELECT COUNT(*) AS n FROM orders o $whereSQL");
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$totalRows = $cntStmt->get_result()->fetch_assoc()['n'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Data
$sql = "SELECT o.id, o.order_code, o.customer_name, o.customer_email, o.delivery_date,
               o.occasion, o.status, o.total_price, o.payment_status, o.created_at
        FROM orders o
        $whereSQL
        ORDER BY o.$sort $dir
        LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Build current filter query string (for pagination / sort links)
$filterParams = array_filter(compact('search', 'status', 'occasion', 'dateFrom', 'dateTo'), fn($v) => $v !== '');
$filterQuery  = http_build_query($filterParams + ['sort' => $sort, 'dir' => $dir]);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-shopping-bag me-2 text-primary-custom"></i>Orders</h1>
  <a href="<?= $adminBase ?>/orders/create.php" class="btn btn-primary">
    <i class="fa fa-plus me-1"></i>New Order
  </a>
</div>

<!-- ── Filters bar ── -->
<div class="filters-bar">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Search</label>
      <input type="text" name="search" class="form-control form-control-sm"
             placeholder="Code, name, email, phone…"
             value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">Status</label>
      <select name="status" class="form-select form-select-sm" id="statusFilter">
        <option value="">All Statuses</option>
        <?php foreach (['new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_', ' ', $s)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">Occasion</label>
      <select name="occasion" class="form-select form-select-sm">
        <option value="">All Occasions</option>
        <?php foreach (['birthday','anniversary','wedding','valentine','mothers_day','corporate','funeral','other'] as $occ): ?>
          <option value="<?= $occ ?>" <?= $occasion === $occ ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_', ' ', $occ)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold mb-1">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm"
             value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
        <i class="fa fa-filter"></i>
      </button>
      <a href="<?= $adminBase ?>/orders/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-times"></i>
      </a>
    </div>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir,  ENT_QUOTES, 'UTF-8') ?>">
  </form>
</div>

<!-- ── Results count ── -->
<p class="text-muted small mb-2">Showing <?= $totalRows ?> order(s)</p>

<!-- ── Table ── -->
<div class="admin-table">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>
            <a href="?<?= $filterQuery ?>&sort=id&dir=<?= $sort==='id'&&$dir==='asc'?'desc':'asc' ?>" class="text-dark text-decoration-none">#</a>
          </th>
          <th>Code</th>
          <th>Customer</th>
          <th>Occasion</th>
          <th>
            <a href="?<?= $filterQuery ?>&sort=delivery_date&dir=<?= $sort==='delivery_date'&&$dir==='asc'?'desc':'asc' ?>" class="text-dark text-decoration-none">Delivery</a>
          </th>
          <th>Status</th>
          <th>Payment</th>
          <th>
            <a href="?<?= $filterQuery ?>&sort=total_price&dir=<?= $sort==='total_price'&&$dir==='asc'?'desc':'asc' ?>" class="text-dark text-decoration-none">Total</a>
          </th>
          <th>
            <a href="?<?= $filterQuery ?>&sort=created_at&dir=<?= $sort==='created_at'&&$dir==='asc'?'desc':'asc' ?>" class="text-dark text-decoration-none">Placed</a>
          </th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($o = $orders->fetch_assoc()): ?>
        <tr>
          <td class="text-muted small"><?= (int)$o['id'] ?></td>
          <td class="fw-semibold text-primary-custom">
            <a href="<?= $adminBase ?>/orders/view.php?id=<?= (int)$o['id'] ?>" class="text-decoration-none">
              <?= htmlspecialchars($o['order_code'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td>
            <div><?= htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <small class="text-muted"><?= htmlspecialchars($o['customer_email'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td class="small text-muted">
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $o['occasion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="small">
            <?= htmlspecialchars(date('d M Y', strtotime($o['delivery_date'])), ENT_QUOTES, 'UTF-8') ?>
            <?php if (strtotime($o['delivery_date']) < time() && !in_array($o['status'], ['delivered','cancelled'])): ?>
              <span class="badge bg-danger ms-1" title="Overdue">!</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="status-badge status-<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>
                         status-change-trigger" style="cursor:pointer"
                  data-id="<?= (int)$o['id'] ?>"
                  data-current="<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars(ucfirst(str_replace('_',' ',$o['status'])), ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td>
            <span class="status-badge payment-<?= htmlspecialchars($o['payment_status'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars(ucfirst($o['payment_status']), ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td class="fw-semibold"><?= number_format((float)$o['total_price'], 2) ?> RON</td>
          <td class="small text-muted">
            <?= htmlspecialchars(date('d M Y', strtotime($o['created_at'])), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= $adminBase ?>/orders/view.php?id=<?= (int)$o['id'] ?>"
                 class="btn btn-sm btn-outline-secondary" title="View">
                <i class="fa fa-eye"></i>
              </a>
              <a href="<?= $adminBase ?>/orders/edit.php?id=<?= (int)$o['id'] ?>"
                 class="btn btn-sm btn-outline-primary" title="Edit">
                <i class="fa fa-edit"></i>
              </a>
              <a href="<?= $adminBase ?>/orders/delete.php?id=<?= (int)$o['id'] ?>"
                 class="btn btn-sm btn-outline-danger" title="Delete"
                 onclick="return confirm('Are you sure you want to delete this order? This cannot be undone.')">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
    <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= $filterQuery ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- ── Status Quick-Change Modal (A6 jQuery) ── -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">Update Order Status</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="statusOrderId">
        <label class="form-label">New Status</label>
        <select id="newStatusSelect" class="form-select">
          <?php foreach (['new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label mt-3">Note (optional)</label>
        <input type="text" id="statusNote" class="form-control" placeholder="Add a note…">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveStatusBtn">Save Status</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  // Open status modal on badge click
  $('.status-change-trigger').on('click', function () {
    const id      = $(this).data('id');
    const current = $(this).data('current');
    $('#statusOrderId').val(id);
    $('#newStatusSelect').val(current);
    $('#statusNote').val('');
    new bootstrap.Modal('#statusModal').show();
  });

  // AJAX status update
  $('#saveStatusBtn').on('click', function () {
    const id     = $('#statusOrderId').val();
    const status = $('#newStatusSelect').val();
    const note   = $('#statusNote').val();

    $.post('<?= $adminBase ?>/orders/update_status.php', {
      id: id,
      status: status,
      note: note,
      csrf_token: '<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>'
    }, function (res) {
      if (res.success) {
        // Update badge text + class using jQuery
        const $badge = $('[data-id="' + id + '"]');
        $badge.attr('class', 'status-badge status-' + status + ' status-change-trigger')
              .text(status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' '))
              .data('current', status);
        bootstrap.Modal.getInstance('#statusModal').hide();
      } else {
        alert('Error updating status: ' + (res.message || 'Unknown error'));
      }
    }, 'json').fail(function () {
      alert('Request failed. Please try again.');
    });
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
