<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: StarLabs AI - Chat AI
// Contoh: {"message":"Halo, apa kabar?"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param message Pertanyaan

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(60);

$message = $_GET['message'] ?? '';

if (!$message) {
    $data = array_merge($credit, [
        'status' => false,
        'message' => 'Parameter message diperlukan',
        'usage' => '?message=pertanyaan'
    ]);
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

define('BASE_URL', 'https://starlabs.biz.id/ai/chat/index.php');

$payload = json_encode([
    'message' => $message,
    'stream' => false,
    'recentHistory' => []
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => BASE_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $curlError) {
    $data = array_merge($credit, [
        'status' => false,
        'message' => 'Gagal koneksi: ' . ($curlError ?: 'no response')
    ]);
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse response dari StarLabs
$aiResponse = $response;
$parsed = json_decode($response, true);
if ($parsed && isset($parsed['response'])) {
    $aiResponse = $parsed['response'];
} elseif ($parsed && isset($parsed['message'])) {
    $aiResponse = $parsed['message'];
}

$data = array_merge($credit, [
    'status' => $httpCode === 200,
    'input' => $message,
    'result' => $aiResponse
]);

// Hapus key asli dari API target
$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>