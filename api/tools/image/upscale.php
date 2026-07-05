<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Cloudinary Image Uploader
// Contoh: {"file": "pilih_gambar.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'File wajib diupload'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('SIGN_API', 'https://cloudinary-tools.netlify.app/.netlify/functions/sign-upload-params');
define('UPLOAD_API', 'https://api.cloudinary.com/v1_1/dtz0urit6/auto/upload');
define('API_KEY', '985946268373735');
define('UPLOAD_PRESET', 'cloudinary-tools');
define('SOURCE', 'ml');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36');

$file = $_FILES['file'];
$fileTmp = $file['tmp_name'];
$fileName = $file['name'];
$fileMime = mime_content_type($fileTmp);
$fileData = file_get_contents($fileTmp);

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $response];
}

function createUploadId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    // Step 1: Get signature
    $timestamp = time();
    
    $signResult = curlPost(SIGN_API, json_encode([
        'paramsToSign' => [
            'timestamp' => $timestamp,
            'upload_preset' => UPLOAD_PRESET,
            'source' => SOURCE
        ]
    ]), [
        'Content-Type: application/json',
        'User-Agent: ' . UA,
        'Accept: */*',
        'Origin: https://cloudinary.com',
        'Referer: https://cloudinary.com/'
    ]);
    
    $signData = json_decode($signResult['body'], true);
    $signature = $signData['signature'] ?? '';
    
    if (empty($signature)) throw new Exception('Gagal ambil signature');
    
    // Step 2: Upload
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="upload_preset"' . "\r\n\r\n";
    $body .= UPLOAD_PRESET . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="source"' . "\r\n\r\n";
    $body .= SOURCE . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="signature"' . "\r\n\r\n";
    $body .= $signature . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="timestamp"' . "\r\n\r\n";
    $body .= $timestamp . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="api_key"' . "\r\n\r\n";
    $body .= API_KEY . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"' . "\r\n";
    $body .= 'Content-Type: ' . $fileMime . "\r\n\r\n";
    $body .= $fileData . "\r\n";
    $body .= '--' . $boundary . '--';
    
    $uploadResult = curlPost(UPLOAD_API, $body, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'User-Agent: ' . UA,
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Origin: https://upload-widget.cloudinary.com',
        'Referer: https://upload-widget.cloudinary.com/',
        'X-Requested-With: XMLHttpRequest',
        'X-Unique-Upload-Id: ' . createUploadId()
    ]);
    
    $uploadData = json_decode($uploadResult['body'], true);
    $resultUrl = $uploadData['secure_url'] ?? $uploadData['url'] ?? '';
    
    if (empty($resultUrl)) throw new Exception('Upload gagal: ' . $uploadResult['body']);
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'url' => $resultUrl,
            'filename' => $fileName
        ]
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>