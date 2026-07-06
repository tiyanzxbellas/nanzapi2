<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Upload.ee File Uploader
// Contoh: {"file": "pilih_file.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

// Cek dulu limit server
$maxUpload = min(
    (int)ini_get('upload_max_filesize') * 1024 * 1024,
    (int)ini_get('post_max_size') * 1024 * 1024
);

// Validasi file
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = [
        UPLOAD_ERR_INI_SIZE   => "File terlalu besar. Max: " . ($maxUpload / 1024 / 1024) . "MB",
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (form limit)',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE    => 'File wajib diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh extension'
    ];
    
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $errorMsg[$errorCode] ?? 'File wajib diupload';
    
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $message
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('BASE', 'https://www.upload.ee');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36');

$file = $_FILES['file'];
$fileTmp = $file['tmp_name'];
$fileName = $file['name'];
$fileSize = $file['size'];
$fileMime = mime_content_type($fileTmp);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$isImage = strpos($fileMime, 'image/') === 0;
$category = $isImage ? 'cat_picture' : 'cat_file';

function curlGet($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: text/html,application/xhtml+xml',
        'Referer: ' . BASE . '/'
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function curlPost($url, $body, $headers = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

try {
    $cookieFile = sys_get_temp_dir() . '/upload_ee_' . uniqid() . '.txt';
    
    // Step 1: Init session
    curlGet(BASE . '/?', $cookieFile);
    
    // Step 2: Get upload ID
    $rnd = time() * 1000;
    $uploadPage = curlGet(BASE . '/ubr_link_upload.php?rnd_id=' . $rnd, $cookieFile);
    
    preg_match('/startUpload\("([^"]+)"/', $uploadPage, $match);
    if (!$match) throw new Exception('Upload ID tidak ditemukan');
    
    $uploadId = $match[1];
    
    // Step 3: Upload file
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="upfile_0"; filename="' . $fileName . '"' . "\r\n";
    $body .= 'Content-Type: ' . $fileMime . "\r\n\r\n";
    $body .= file_get_contents($fileTmp) . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="link"' . "\r\n\r\n\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="email"' . "\r\n\r\n\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="category"' . "\r\n\r\n";
    $body .= $category . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="big_resize"' . "\r\n\r\n";
    $body .= 'none' . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="small_resize"' . "\r\n\r\n";
    $body .= '120x90' . "\r\n";
    $body .= '--' . $boundary . '--';
    
    $uploadUrl = BASE . '/cgi-bin/ubr_upload.pl?X-Progress-ID=' . $uploadId . '&upload_id=' . $uploadId;
    
    curlPost($uploadUrl, $body, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'User-Agent: ' . UA,
        'Accept: text/html',
        'Origin: ' . BASE,
        'Referer: ' . BASE . '/?'
    ], $cookieFile);
    
    // Step 4: Get result
    $resultPage = curlGet(BASE . '/?page=finished&upload_id=' . $uploadId, $cookieFile);
    
    preg_match('/id=["\']file_src["\'][^>]*value=["\']([^"\']+)["\']/i', $resultPage, $srcMatch);
    preg_match('/View file:\s*<br\s*\/?>\s*<a href=["\']?([^"\'>\s]+)["\']?/i', $resultPage, $viewMatch);
    
    $viewUrl = $srcMatch[1] ?? $viewMatch[1] ?? null;
    
    if (!$viewUrl) throw new Exception('View URL tidak ditemukan');
    
    $resultUrl = $viewUrl;
    
    // Kalau image, convert ke direct URL
    if ($isImage) {
        $resultUrl = str_replace('/files/', '/image/', $viewUrl);
        $resultUrl = preg_replace('/\.html$/', '', $resultUrl);
    }
    
    @unlink($cookieFile);
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'url' => $resultUrl,
            'type' => $isImage ? 'image' : 'file',
            'filename' => $fileName
        ]
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    @unlink($cookieFile ?? '');
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>