<?php
/**
 * admin/orders/pdf.php — Generate and output PDF for order (A9)
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/lib/pdf_generator.php';
requireAdminLogin();

$conn = getDbConnection();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { http_response_code(404); echo "Order not found."; exit; }

$iStmt = $conn->prepare(
    "SELECT oi.*, p.name AS product_name FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?"
);
$iStmt->bind_param('i', $id);
$iStmt->execute();
$items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pdf = new FlowerPDFGenerator();
$pdf->generate($order, $items);
