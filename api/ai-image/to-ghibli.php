<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: AI Ghibli Transformer
// Contoh: {"file": "foto.jpg"} atau {"url": "https://example.com/foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL gambar (opsional kalau pakai file)

set_time_limit(180);

$credit = 'Nanzz';
$imageUrl = $_GET['url'] ?? '';
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
$prompt = 'Transform this person into Studio Ghibli anime style. Give them large expressive anime eyes, soft features, and that signature Ghibli art style. Place them in a beautiful Ghibli-style background with soft watercolor-like colors, gentle lighting, and magical atmosphere. Keep the face recognizable but in Ghibli art style. Add some Ghibli elements like floating spirits, lush nature, or whimsical details. Do not change the person completely, just apply Ghibli art filter.';

if (empty($imageUrl) && !$hasFile) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Upload file atau masukkan URL']);
    exit;
}

// Upload ke CDN
if ($hasFile) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileMime = mime_content_type($fileTmp);
    
    $ch = curl_init('https://cdnn.ikyyxd.my.id/api/upload.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($fileTmp, $fileMime, $_FILES['file']['name'])]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $uploadResponse = curl_exec($ch);
    curl_close($ch);
    $uploadData = json_decode($uploadResponse, true);
    $imageUrl = $uploadData['url'] ?? $uploadData['result']['url'] ?? '';
}

if (empty($imageUrl)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Upload gagal']);
    exit;
}

// Edit gambar
$apiUrl = 'https://api.ikyyxd.my.id/edit/nanobananav3?url=' . urlencode($imageUrl) . '&prompt=' . urlencode($prompt);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$resultUrl = $data['result']['result_url'] ?? $data['result']['url'] ?? '';

if (empty($resultUrl)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Edit gagal']);
    exit;
}

// Download & output
$ch = curl_init($resultUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$img = curl_exec($ch);
curl_close($ch);

header('Content-Type: image/png');
header('X-Creator: ' . $credit);
echo $img;
?>