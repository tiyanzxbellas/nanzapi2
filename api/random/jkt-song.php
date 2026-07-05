<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random JKT48 Audio - Stream audio random JKT48
// Contoh: {} (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: audio/mpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

// Tambahin random query biar ga di-cache
$apiUrl = 'https://smail.my.id/randomlagujkt48?type=buffer&t=' . time() . rand(1000, 9999);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
        'Accept: audio/*',
        'Referer: https://smail.my.id/',
        'Cache-Control: no-cache',
        'Pragma: no-cache'
    ]
]);

$audioData = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $audioData) {
    header('Content-Type: ' . ($contentType ?: 'audio/mpeg'));
    header('Content-Length: ' . strlen($audioData));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo $audioData;
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal mendapatkan audio JKT48'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>