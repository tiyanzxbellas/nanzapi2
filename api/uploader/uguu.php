<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Uguu.se File Uploader
// Contoh: {"file": "pilih_file.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(120);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'File wajib diupload (POST multipart/form-data)'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$filePath = $file['tmp_name'];
$fileName = $file['name'];

define('ENDPOINT', 'https://uguu.se/upload.php');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36');

try {
    // Build multipart form data
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="files[]"; filename="' . $fileName . '"' . "\r\n";
    $body .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
    $body .= file_get_contents($filePath) . "\r\n";
    $body .= '--' . $boundary . '--';
    
    $ch = curl_init(ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Accept: */*',
            'Origin: https://uguu.se',
            'Referer: https://uguu.se/',
            'User-Agent: ' . UA,
            'Sec-Ch-Ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
            'Sec-Ch-Ua-Mobile: ?1',
            'Sec-Ch-Ua-Platform: "Android"',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    $url = $result['files'][0]['url'] ?? null;
    $success = $httpCode === 200 && ($result['success'] ?? false) && $url;
    
    echo json_encode(array_merge($credit, [
        'status' => $success,
        'code' => $httpCode,
        'input' => $fileName,
        'url' => $url,
        'message' => $success ? 'Upload berhasil' : 'Upload gagal'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>