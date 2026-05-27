<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdminLogin();

$adminPageTitle = 'Contact Messages';
$adminBase      = '/Final_Project__DSS_Mitea_Diana-Maria/admin';
$conn           = getDbConnection();

// Mark as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $conn->execute_query("UPDATE contact_messages SET is_read=1 WHERE id=?", [(int)$_GET['read']]);
    header("Location: $adminBase/messages/index.php");
    exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '') && isset($_POST['delete_id'])) {
    $conn->execute_query("DELETE FROM contact_messages WHERE id=?", [(int)$_POST['delete_id']]);
    setFlash('success', 'Message deleted.');
    header("Location: $adminBase/messages/index.php");
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter === 'unread' ? 'WHERE is_read = 0' : '';

$messages = $conn->query(
    "SELECT * FROM contact_messages $where ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$unreadCount = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetch_row()[0];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa fa-envelope me-2 text-primary-custom"></i>Contact Messages
    <?php if ($unreadCount > 0): ?>
      <span class="badge bg-danger ms-2"><?= $unreadCount ?> new</span>
    <?php endif; ?>
  </h1>
</div>

<?= renderFlash() ?>

<div class="mb-3 d-flex gap-2">
  <a href="?filter=all"    class="btn btn-sm <?= $filter === 'all'    ? 'btn-primary' : 'btn-outline-secondary' ?>">All (<?= count($messages) ?>)</a>
  <a href="?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' ?>">Unread (<?= $unreadCount ?>)</a>
</div>

<div class="admin-table">
  <?php if (empty($messages)): ?>
    <div class="text-center text-muted py-5">No messages found.</div>
  <?php else: ?>
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>From</th>
          <th>Subject</th>
          <th>Message</th>
          <th>Date</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $msg): ?>
        <tr <?= !$msg['is_read'] ? 'class="table-warning fw-semibold"' : '' ?>>
          <td>
            <div><?= htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <small class="text-muted"><?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td><?= htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted small" style="max-width:300px">
            <?= htmlspecialchars(mb_substr($msg['message'], 0, 100), ENT_QUOTES, 'UTF-8') ?>
            <?= strlen($msg['message']) > 100 ? '…' : '' ?>
          </td>
          <td class="small text-muted text-nowrap"><?= date('d M Y H:i', strtotime($msg['created_at'])) ?></td>
          <td class="text-center text-nowrap">
            <?php if (!$msg['is_read']): ?>
              <a href="?read=<?= $msg['id'] ?>" class="btn btn-xs btn-outline-success me-1"
                 style="font-size:.75rem;padding:2px 8px" title="Mark as read">
                <i class="fa fa-check"></i>
              </a>
            <?php endif; ?>
            <button type="button" class="btn btn-xs btn-outline-info me-1"
                    style="font-size:.75rem;padding:2px 8px"
                    data-bs-toggle="modal" data-bs-target="#msgModal<?= $msg['id'] ?>">
              <i class="fa fa-eye"></i>
            </button>
            <form method="post" class="d-inline"
                  onsubmit="return confirm('Delete this message?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="delete_id"  value="<?= $msg['id'] ?>">
              <button type="submit" class="btn btn-xs btn-outline-danger"
                      style="font-size:.75rem;padding:2px 8px">
                <i class="fa fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>

        <!-- Message modal -->
        <div class="modal fade" id="msgModal<?= $msg['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p class="mb-1"><strong>From:</strong> <?= htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-1"><strong>Email:</strong>
                  <a href="mailto:<?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </p>
                <p class="mb-3"><strong>Date:</strong> <?= date('d M Y H:i', strtotime($msg['created_at'])) ?></p>
                <hr>
                <p class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <div class="modal-footer">
                <?php if (!$msg['is_read']): ?>
                  <a href="?read=<?= $msg['id'] ?>" class="btn btn-success btn-sm">
                    <i class="fa fa-check me-1"></i>Mark as Read
                  </a>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
