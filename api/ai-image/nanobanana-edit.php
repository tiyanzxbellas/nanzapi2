<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: NanoBanana AI Image Editor
// Contoh: {"file": "foto.jpg", "prompt": "ubah jadi sketsa pensil"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param prompt Text prompt edit

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$prompt = $_POST['prompt'] ?? $_GET['prompt'] ?? '';

if (empty($prompt)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter prompt wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'File gambar wajib diupload'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$fileTmp = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$fileMime = mime_content_type($fileTmp);

// Upload ke GoBox
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
$imageUrl = $uploadData['url'] ?? $uploadData['data']['url'] ?? '';

if (empty($imageUrl)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Upload gambar gagal'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Panggil API Ikyy
$apiUrl = 'https://api.ikyyxd.my.id/edit/nanobananav3?url=' . urlencode($imageUrl) . '&prompt=' . urlencode($prompt);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Edit gambar gagal (HTTP ' . $http_code . ')'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

if (!($data['status'] ?? false)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Edit gagal'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$resultUrl = $data['result']['result_url'] ?? $data['result']['url'] ?? '';

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => [
        'url' => $resultUrl,
        'prompt' => $prompt
    ]
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>