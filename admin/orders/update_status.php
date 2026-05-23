<?php
/**
 * admin/orders/update_status.php — AJAX endpoint for status update (A6)
 * POST: id, status, note, csrf_token
 * Returns JSON
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF check failed']);
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$note   = trim($_POST['note'] ?? '');

$allowed = ['new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
if (!in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$conn = getDbConnection();

// Fetch current
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
if (!$current) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Update
$conn->begin_transaction();
try {
    $conn->prepare("UPDATE orders SET status=? WHERE id=?")->execute_query([$status, $id]);

    $adminName = $_SESSION['admin_name'] ?? 'Admin';
    if (!$note) {
        $note = "Status changed from {$current['status']} to {$status}";
    }
    $conn->prepare(
        "INSERT INTO status_history (order_id, new_status, changed_by, notes) VALUES (?,?,?,?)"
    )->execute_query([$id, $status, $adminName, $note]);

    $conn->commit();
    echo json_encode(['success' => true, 'status' => $status]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
