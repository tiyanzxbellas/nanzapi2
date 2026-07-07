<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Catbox.moe Uploader dengan userhash
// Contoh: {"file": "pilih_file.jpg"}

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File wajib diupload');
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];

    if ($fileSize > 200 * 1024 * 1024) {
        throw new Exception('File terlalu besar (max 200MB)');
    }

    // ========== PAKAI USERHASH ==========
    $postData = [
        'reqtype' => 'fileupload',
        'userhash' => 'c68d43bc9f81df99573110c40', // USERHASH ANDA
        'fileToUpload' => new CURLFile($fileTmp, mime_content_type($fileTmp), $fileName)
    ];

    $ch = curl_init('https://catbox.moe/user/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_REFERER, 'https://catbox.moe/');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }

    if ($http_code !== 200) {
        throw new Exception('HTTP Error: ' . $http_code . ' - ' . $response);
    }

    $url = trim($response);
    
    if (empty($url) || strpos($url, 'https://') !== 0) {
        throw new Exception('Upload gagal: ' . $url);
    }

    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'url' => $url,
            'filename' => $fileName,
            'size' => $fileSize
        ]
    ]));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]));
}
?>
