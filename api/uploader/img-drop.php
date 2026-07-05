<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Image Uploader (ImgDrop)
// Contoh: {"file": "pilih_gambar.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'File wajib diupload']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$file = $_FILES['file'];
$fileTmp = $file['tmp_name'];
$fileName = $file['name'];
$fileMime = mime_content_type($fileTmp);
$fileSize = $file['size'];

if ($fileSize > 10 * 1024 * 1024) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'File terlalu besar (max 10MB)']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$ch = curl_init('https://imgdrop.web.id/upload.php');
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

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Upload gagal (HTTP ' . $http_code . ')']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

if (!($data['success'] ?? false)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Upload gagal']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array_merge(
    $credit,
    [
        'status' => true,
        'result' => $data
    ]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>