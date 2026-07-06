<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Sticker.ly - Cari Sticker Pack
// Contoh: {"query":"jokowi"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param query Kata Kunci Pencarian

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$query = $_GET['query'] ?? '';

if (!$query) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter query diperlukan'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

define('UA', 'androidapp.stickerly/3.31.0 (M2006C3LG; U; Android 29; in-ID; id;)');

$payload = json_encode([
    'keyword' => $query,
    'enabledKeywordSearch' => true,
    'filter' => [
        'extendSearchResult' => false,
        'sortBy' => 'RECOMMENDED',
        'languages' => ['ALL'],
        'minStickerCount' => 5,
        'searchBy' => 'ALL',
        'stickerType' => 'ALL'
    ]
]);

$ch = curl_init('https://api.sticker.ly/v4/stickerPack/smartSearch');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: ' . UA,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if (!$response || $curlError) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Gagal koneksi: ' . ($curlError ?: 'no response')
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$result = json_decode($response, true);

// Hapus key creator/author dari API target, tapi pertahankan punya kita
$keysToRemove = ['Creator', 'author', 'Author'];
$result = removeKeysRecursive($result, $keysToRemove);

echo json_encode(array_merge($credit, [
    'status' => true,
    'input' => $query,
    'result' => $result
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>