<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: AI Skin Darkener (Output Gambar)
// Contoh: {"file": "foto.jpg"} atau {"url": "https://example.com/foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL gambar (opsional kalau pakai file)

set_time_limit(180);

$credit = 'Tiyanz';
$imageUrl = $_GET['url'] ?? '';
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
$prompt = 'Make the skin extremely dark black, very very dark brown like charcoal. Darken the skin to the maximum level. Keep all other elements completely unchanged - clothes, background, hair, eyes, lips, accessories, lighting, everything except skin tone. Only the skin should become very dark black. Make it look natural dark skin, not painted.';

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