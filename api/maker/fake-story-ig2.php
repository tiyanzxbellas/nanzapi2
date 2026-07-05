<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Instagram Story Canvas Maker
// Contoh: {"file": "foto.jpg", "name": "John Doe", "text": "Hello World"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param name Nama pengguna
// @param text Teks story

header('Content-Type: image/png');
set_time_limit(60);

// ========== CREDIT ==========
$credit = 'Nanzz';

$name = $_POST['name'] ?? $_GET['name'] ?? 'User';
$text = $_POST['text'] ?? $_GET['text'] ?? '';
$avatarUrl = '';

if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => $credit,
        'status' => false,
        'message' => 'Parameter text wajib diisi'
    ]);
    exit;
}

// Upload avatar ke GoBox
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileMime = mime_content_type($fileTmp);
    
    $ch = curl_init('https://www.gobox.my.id/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($fileTmp, $fileMime, $fileName)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0',
        'Accept: application/json'
    ]);
    
    $uploadResponse = curl_exec($ch);
    curl_close($ch);
    
    $uploadData = json_decode($uploadResponse, true);
    $avatarUrl = $uploadData['url'] ?? $uploadData['data']['url'] ?? '';
}

if (empty($avatarUrl)) {
    $avatarUrl = 'https://files.covenant.sbs/1f5c7d2a-6671-400e-9005-66439d560094.jpeg';
}

// Generate IG Story
$apiUrl = 'https://omegatech-api.dixonomega.tech/api/Maker/igstory-canvas?avatarUrl=' . urlencode($avatarUrl) . '&name=' . urlencode($name) . '&text=' . urlencode($text);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36'
]);

$imageData = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code !== 200 || empty($imageData)) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => $credit,
        'status' => false,
        'message' => 'Generate gagal (HTTP ' . $http_code . ')'
    ]);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'image/png'));
header('X-Creator: ' . $credit);
echo $imageData;
?>