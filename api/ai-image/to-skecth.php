<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Artyde Photo to Sketch
// Contoh: {"file": "foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120);

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

$fileTmp = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$fileMime = mime_content_type($fileTmp);
$fileData = file_get_contents($fileTmp);

define('BASE', 'https://artyde.com');
define('UPLOAD_URL', BASE . '/upload_photoToSketch_website');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36');

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function curlHead($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: */*',
        'Referer: ' . BASE . '/'
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code;
}

try {
    // Upload fields to try
    $uploadFields = ['file', 'image', 'img', 'photo', 'source_image', 'upload'];
    $uploaded = null;
    
    foreach ($uploadFields as $field) {
        $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
        
        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="' . $field . '"; filename="' . $fileName . '"' . "\r\n";
        $body .= 'Content-Type: ' . $fileMime . "\r\n\r\n";
        $body .= $fileData . "\r\n";
        $body .= '--' . $boundary . '--';
        
        $result = curlPost(UPLOAD_URL, $body, [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'User-Agent: ' . UA,
            'Accept: */*',
            'Origin: ' . BASE,
            'Referer: ' . BASE . '/photo_to_sketch'
        ]);
        
        if ($result && ($result['status'] ?? '') === 'success' && !empty($result['file_id'])) {
            $uploaded = $result;
            break;
        }
    }
    
    if (!$uploaded) throw new Exception('Upload gagal');
    
    $fileId = $uploaded['file_id'];
    $outputName = preg_replace('/\.[^.]+$/', '.png', $fileId);
    $outputUrl = BASE . '/output/' . $outputName;
    
    // Polling
    $maxAttempts = 25;
    $interval = 3;
    $done = false;
    
    for ($i = 1; $i <= $maxAttempts; $i++) {
        sleep($interval);
        
        $httpCode = curlHead($outputUrl);
        
        if ($httpCode === 200) {
            $done = true;
            break;
        }
    }
    
    if (!$done) throw new Exception('Timeout menunggu hasil');
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'output_url' => $outputUrl,
            'file_id' => $fileId
        ]
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>