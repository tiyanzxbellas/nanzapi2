<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: FileGoat File Uploader
// Contoh: {"file": "pilih_file.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(60);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $data = array_merge($credit, ['status' => false, 'message' => 'File wajib diupload']);
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$file = $_FILES['file'];
$filePath = $file['tmp_name'];
$fileName = $file['name'];
$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

define('BASE', 'https://filego.at');
define('S3_BASE', 'https://filegoat.s3.de.io.cloud.ovh.net');

function curlPost($url, $data, $headers = []) {
    $ch = curl_init($url);
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36',
        'Origin: ' . BASE, 'Referer: ' . BASE . '/',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers)
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

function curlGet($url, $headers = []) {
    $ch = curl_init($url);
    $defaultHeaders = ['User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers)
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

// Step 1: Upload file
$boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
$body = '';
$body .= '--' . $boundary . "\r\n";
$body .= 'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"' . "\r\n";
$body .= 'Content-Type: ' . $mimeType . "\r\n\r\n";
$body .= file_get_contents($filePath) . "\r\n";
$body .= '--' . $boundary . '--';

$uploadRes = curlPost(BASE . '/api/file/upload', $body, [
    'Content-Type: multipart/form-data; boundary=' . $boundary,
    'Accept: application/json, text/plain, */*'
]);

$fileIds = $uploadRes['fileIds'] ?? $uploadRes['ids'] ?? [$uploadRes['id'] ?? null];
$fileIds = array_filter($fileIds);

if (empty($fileIds)) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Upload gagal']), JSON_PRETTY_PRINT);
    exit;
}

// Step 2: Create bucket
$clientId = bin2hex(random_bytes(16));
$bucketRes = curlPost(BASE . '/api/bucket', json_encode([
    'fileIds' => array_values($fileIds), 'deleteTime' => 7, 'extendOnView' => false, 'clientId' => $clientId
]), ['Content-Type: application/json', 'Accept: */*']);

if (empty($bucketRes['slug'])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Bucket gagal']), JSON_PRETTY_PRINT);
    exit;
}

$slug = $bucketRes['slug'];

// Step 3: Get bucket detail
$detail = curlGet(BASE . '/api/bucket/' . $slug, ['Accept: application/json']);

$files = [];
foreach ($detail['files'] ?? [] as $f) {
    $name = $f['fileName'] ?? $f['file_name'] ?? '';
    $saved = $f['savedName'] ?? $f['saved_name'] ?? '';
    $files[] = [
        'name' => $name,
        'size' => $f['bytes'] ?? 0,
        'direct' => S3_BASE . '/' . $saved . '/' . $name,
        'download' => S3_BASE . '/' . $saved . '/' . $name . '?download=true'
    ];
}

$data = array_merge($credit, [
    'status' => true,
    'slug' => $slug,
    'url' => BASE . '/bucket/' . $slug,
    'expires' => $bucketRes['delete_time'] ?? null,
    'files' => $files
]);

$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>