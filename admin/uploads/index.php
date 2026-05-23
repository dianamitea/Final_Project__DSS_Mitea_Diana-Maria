<?php
/**
 * admin/uploads/index.php — File upload management (A8)
 * Validates MIME, size, stores to DB
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'File Uploads';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $orderId  = (int)($_POST['order_id'] ?? 0);
    $fileNote = trim($_POST['note'] ?? '');

    if (!empty($_FILES['upload_file']['name'])) {
        $file    = $_FILES['upload_file'];
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];
        $maxSize = 20 * 1024 * 1024;
        $mime    = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error code: ' . $file['error'];
        } elseif (!array_key_exists($mime, $allowed)) {
            $errors[] = 'File type not allowed. Accepted: JPEG, PNG, GIF, WEBP, PDF.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'File exceeds 20MB limit.';
        } else {
            $ext       = $allowed[$mime];
            $safeName  = uniqid('upload_') . '_' . preg_replace('/[^a-z0-9._-]/i', '_', basename($file['name']));
            $uploadDir = dirname(__DIR__, 2) . '/assets/uploads/files/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $dest = $uploadDir . $safeName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $relPath  = 'assets/uploads/files/' . $safeName;
                $origName = htmlspecialchars(basename($file['name']), ENT_QUOTES, 'UTF-8');
                $uploader = $_SESSION['admin_name'];

                $conn->prepare(
                    "INSERT INTO uploaded_files (order_id, file_name, original_name, file_path, file_type, file_size, uploaded_by)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute_query([
                    $orderId ?: null, $safeName, $origName, $relPath,
                    $mime, $file['size'], $uploader
                ]);

                setFlash('success', 'File "' . $origName . '" uploaded successfully.');
                header("Location: $adminBase/uploads/index.php");
                exit;
            } else {
                $errors[] = 'Failed to move uploaded file.';
            }
        }
    } else {
        $errors[] = 'No file selected.';
    }
}

// Paginate uploads
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$total   = $conn->query("SELECT COUNT(*) AS n FROM uploaded_files")->fetch_assoc()['n'];
$offset  = ($page - 1) * $perPage;
$pages   = max(1, (int)ceil($total / $perPage));

$uploads = $conn->query(
    "SELECT uf.*, o.order_code FROM uploaded_files uf
     LEFT JOIN orders o ON uf.order_id = o.id
     ORDER BY uf.uploaded_at DESC LIMIT $perPage OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

$orders = $conn->query("SELECT id, order_code, customer_name FROM orders ORDER BY created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-upload me-2 text-primary-custom"></i>File Uploads</h1>
</div>

<!-- Upload form (A8) -->
<div class="admin-form-card mb-4">
  <div class="form-section-title">Upload New File</div>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-md-4">
      <label class="form-label">File *</label>
      <input type="file" name="upload_file" class="form-control" accept="image/*,.pdf" required>
      <small class="text-muted">JPEG, PNG, GIF, WEBP, PDF — max 20MB</small>
    </div>
    <div class="col-md-4">
      <label class="form-label">Link to Order (optional)</label>
      <select name="order_id" class="form-select">
        <option value="">No order</option>
        <?php foreach ($orders as $o): ?>
          <option value="<?= (int)$o['id'] ?>">
            <?= htmlspecialchars($o['order_code'] . ' — ' . $o['customer_name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">
        <i class="fa fa-upload me-2"></i>Upload File
      </button>
    </div>
  </form>
</div>

<!-- File list -->
<div class="admin-table">
  <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
    <h6 class="fw-bold mb-0">Uploaded Files (<?= $total ?>)</h6>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Filename</th>
          <th>Type</th>
          <th>Size</th>
          <th>Order</th>
          <th>Uploaded By</th>
          <th>Date</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($uploads)): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">No files uploaded yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($uploads as $f): ?>
        <tr>
          <td>
            <i class="fa <?= str_contains($f['file_type'], 'pdf') ? 'fa-file-pdf text-danger' : 'fa-file-image text-primary-custom' ?> me-2"></i>
            <?= htmlspecialchars($f['original_name'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="small text-muted"><?= htmlspecialchars($f['file_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="small"><?= round($f['file_size'] / 1024) ?> KB</td>
          <td class="small">
            <?php if ($f['order_code']): ?>
              <a href="<?= $adminBase ?>/orders/view.php?id=<?= (int)$f['order_id'] ?>"
                 class="text-primary-custom">
                <?= htmlspecialchars($f['order_code'], ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="small"><?= htmlspecialchars($f['uploaded_by'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="small text-muted">
            <?= htmlspecialchars(date('d M Y', strtotime($f['uploaded_at'])), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="/Final_Project__DSS_Mitea_Diana-Maria/<?= htmlspecialchars($f['file_path'], ENT_QUOTES, 'UTF-8') ?>"
                 class="btn btn-sm btn-outline-secondary" target="_blank" title="Download">
                <i class="fa fa-download"></i>
              </a>
              <a href="<?= $adminBase ?>/uploads/delete.php?id=<?= (int)$f['id'] ?>"
                 class="btn btn-sm btn-outline-danger" title="Delete"
                 onclick="return confirm('Delete this file?')">
                <i class="fa fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="d-flex justify-content-center py-3 border-top">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
