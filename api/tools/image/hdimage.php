<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: SparkPix HD Image Upscaler
// Contoh: {"file": "foto.jpg", "quality": "4k"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param quality (4k|6k|8k) Kualitas upscale

set_time_limit(120);

$credit = 'Tiyanz';
$quality = $_POST['quality'] ?? $_GET['quality'] ?? '4k';

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'File wajib diupload']);
    exit;
}

define('BASE', 'https://sparkpix.ai');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36');

$file = $_FILES['file'];
$fileData = file_get_contents($file['tmp_name']);
$fileMime = mime_content_type($file['tmp_name']);
$fileSize = $file['size'];
$fileName = $file['name'];

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function curlPut($url, $data, $mime, $size) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $mime, 'Content-Length: ' . $size]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$scale = $quality === '8k' ? 4 : ($quality === '6k' ? 3 : 2);

try {
    // Step 1: Get upload URL
    $upload = curlPost(BASE . '/api/upload-url', [
        'contentType' => $fileMime, 'size' => $fileSize, 'fileName' => $fileName
    ], ['Content-Type: application/json', 'User-Agent: ' . UA, 'Origin: ' . BASE]);
    
    if (!($upload['success'] ?? false)) throw new Exception('Gagal upload URL');
    
    // Step 2: Upload
    curlPut($upload['uploadUrl'], $fileData, $fileMime, $fileSize);
    
    // Step 3: Upscale
    $result = curlPost(BASE . '/api/free-hd-upscale', [
        'imageUrl' => $upload['publicUrl'], 'scale' => $scale, 'face_enhance' => false
    ], ['Content-Type: application/json', 'User-Agent: ' . UA, 'Origin: ' . BASE]);
    
    if (!($result['success'] ?? false)) throw new Exception('Upscale gagal');
    
    // Download & output
    $img = curlGet($result['resultUrl']);
    
    header('Content-Type: image/png');
    header('X-Creator: ' . $credit);
    echo $img;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => $e->getMessage()]);
}
?>