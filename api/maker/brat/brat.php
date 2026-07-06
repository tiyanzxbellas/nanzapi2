<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Brat Generator via Yupra API
// Contoh: {"text":"Hai"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$text = $_GET['text'] ?? 'Hai';

if (!$text) {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter text diperlukan']), JSON_PRETTY_PRINT);
    exit;
}

$apiUrl = 'https://api.yupra.my.id/api/image/brat?text=' . urlencode($text);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
]);

$imageData = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $imageData) {
    header('Content-Type: ' . ($contentType ?: 'image/png'));
    header('Content-Length: ' . strlen($imageData));
    echo $imageData;
} else {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal generate brat']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>