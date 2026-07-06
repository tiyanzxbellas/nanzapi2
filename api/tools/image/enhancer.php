<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Image Enhancer (hapus input URL jika menggunakan file) 
// Contoh: {"file": "foto.jpg"} atau {"url": "https://example.com/foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL gambar (opsional kalau pakai file)

set_time_limit(60);

$credit = 'Tiyanz';
$imageUrl = $_GET['url'] ?? '';
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

if (empty($imageUrl) && !$hasFile) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter url atau file wajib diisi']);
    exit;
}

if ($hasFile) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileMime = mime_content_type($fileTmp);
    
    $ch = curl_init('https://www.gobox.my.id/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($fileTmp, $fileMime, $fileName)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0', 'Accept: application/json']);
    $uploadResponse = curl_exec($ch);
    curl_close($ch);
    $uploadData = json_decode($uploadResponse, true);
    $imageUrl = $uploadData['url'] ?? $uploadData['data']['url'] ?? '';
    
    if (empty($imageUrl)) {
        header('Content-Type: application/json');
        echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Upload gagal']);
        exit;
    }
}

$ch = curl_init('https://api-varhad.my.id/tools/remini?imageUrl=' . urlencode($imageUrl));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);

$imageData = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code !== 200 || empty($imageData)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Enhance gagal']);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'image/png'));
header('X-Creator: ' . $credit);
echo $imageData;
?>