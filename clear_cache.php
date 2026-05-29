<?php
require_once __DIR__ . '/includes/db.php';
$conn = getDbConnection();
$conn->query("DELETE FROM api_cache WHERE cache_key = 'exchange_rates_RON'");
echo "Deleted: " . $conn->affected_rows . " cached rows\n";

// Also show what the API returns now
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://v6.exchangerate-api.com/v6/4d8fb9af73c6015614fd7b32/latest/RON',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);
echo "HTTP: $code\n";
echo "result: " . ($data['result'] ?? 'n/a') . "\n";
echo "Keys in response: " . implode(', ', array_keys($data ?? [])) . "\n";
echo "EUR rate: " . ($data['conversion_rates']['EUR'] ?? $data['rates']['EUR'] ?? 'NOT FOUND') . "\n";
