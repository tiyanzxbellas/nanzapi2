<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: MLBB Fake Rank Generator
// Contoh: {"username":"Owiee","avatar":"https://www.upload.ee/image/19400325/images.webp","rank":"imo","border":"0"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param username Username
// @param avatar URL Avatar
// @param rank (epic|glory|gm|honor|imo|mawi|legend) Rank
// @param border (0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16) Border

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$username = $_GET['username'] ?? 'Player';
$avatar = $_GET['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde';
$rank = $_GET['rank'] ?? 'imo';
$border = $_GET['border'] ?? '0';

$apiUrl = 'https://anabot.my.id/api/maker/ML-fake?username=' . urlencode($username) 
    . '&avatar=' . urlencode($avatar) 
    . '&rank=' . urlencode($rank) 
    . '&border=' . urlencode($border) 
    . '&apikey=freeApikey';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0'
    ]
]);

$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    if (strpos($contentType, 'image') !== false) {
        header('Content-Type: ' . $contentType);
        echo $response;
    } else {
        $json = json_decode($response, true);
        $imageUrl = $json['url'] ?? $json['result'] ?? $json['image'] ?? null;
        
        if ($imageUrl) {
            header('Content-Type: image/png');
            $ch2 = curl_init($imageUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            echo curl_exec($ch2);
            curl_close($ch2);
        } else {
            header('Content-Type: application/json');
            echo json_encode(array_merge($credit, [
                'status' => false,
                'message' => 'Gagal mendapatkan gambar',
                'raw' => $json
            ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Gagal generate MLBB Rank'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>