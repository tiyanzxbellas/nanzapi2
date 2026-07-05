<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Brat Generator - Video
// Contoh: {"text":"Hahaha lu siapa"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks

header('Content-Type: video/mp4');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$text = $_GET['text'] ?? 'Hello world!';

if (!$text) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Parameter text diperlukan'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$apiUrl = 'https://api.yupra.my.id/api/video/bratv?text=' . urlencode($text);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
]);

$videoData = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $videoData) {
    header('Content-Type: ' . ($contentType ?: 'video/mp4'));
    header('Content-Length: ' . strlen($videoData));
    header('Cache-Control: no-cache');
    header('Accept-Ranges: bytes');
    echo $videoData;
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal generate brat video'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>