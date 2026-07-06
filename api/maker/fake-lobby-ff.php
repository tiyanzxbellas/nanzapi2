<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Free Fire Lobby Generator
// Contoh: {"nickname":"Nanas","versi":"11"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nickname Nickname FF
// @param versi (1|2|3|4|5|6|7|8|9|10|11) Versi

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$nickname = $_GET['nickname'] ?? 'Player';
$versi = $_GET['versi'] ?? '11';

$apiUrl = 'https://api.theresav.biz.id/canvas/lobyff?nickname=' . urlencode($nickname) . '&versi=' . urlencode($versi) . '&apikey=xPt78';

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
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Gagal generate FF Lobby'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>