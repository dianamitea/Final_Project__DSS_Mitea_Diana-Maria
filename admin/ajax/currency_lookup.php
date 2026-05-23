<?php
/**
 * admin/ajax/currency_lookup.php — AJAX endpoint for exchange rates (A10)
 * Fetches from open.er-api.com, caches result in api_cache table
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

$conn     = getDbConnection();
$cacheKey = 'exchange_rates_RON';
$cacheTtl = 3600; // 1 hour

// ── Check cache ──
$cacheStmt = $conn->prepare(
    "SELECT response_data, cached_at FROM api_cache WHERE cache_key = ? AND cached_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
);
$cacheStmt->bind_param('si', $cacheKey, $cacheTtl);
$cacheStmt->execute();
$cached = $cacheStmt->get_result()->fetch_assoc();

if ($cached) {
    $data = json_decode($cached['response_data'], true);
    echo json_encode([
        'success'   => true,
        'rates'     => $data['rates'] ?? [],
        'base'      => $data['base_code'] ?? 'RON',
        'source'    => 'cache',
        'fetched_at'=> $cached['cached_at']
    ]);
    exit;
}

// ── Fetch from API ──
$apiUrl = 'https://open.er-api.com/v6/latest/RON';
$ch     = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'PetalsBloom-DSS/1.0',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'API request failed: ' . ($curlErr ?: "HTTP $httpCode")
    ]);
    exit;
}

$data = json_decode($response, true);
if (!$data || ($data['result'] ?? '') !== 'success') {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API response: ' . ($data['error-type'] ?? 'unknown')
    ]);
    exit;
}

// ── Store / update cache ──
$upsert = $conn->prepare(
    "INSERT INTO api_cache (cache_key, response_data, cached_at)
     VALUES (?, ?, NOW())
     ON DUPLICATE KEY UPDATE response_data = VALUES(response_data), cached_at = NOW()"
);
$upsert->bind_param('ss', $cacheKey, $response);
$upsert->execute();

// Return selected currencies (relative to RON)
echo json_encode([
    'success'   => true,
    'rates'     => $data['rates'] ?? [],
    'base'      => $data['base_code'] ?? 'RON',
    'source'    => 'api',
    'fetched_at'=> date('Y-m-d H:i:s')
]);
