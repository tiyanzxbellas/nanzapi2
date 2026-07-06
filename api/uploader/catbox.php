<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Catbox.moe Uploader
// Contoh: {"file": "pilih_file.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

try {
    // Cek apakah file diupload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File wajib diupload');
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];

    // Batas ukuran file 200MB
    if ($fileSize > 200 * 1024 * 1024) {
        throw new Exception('File terlalu besar (max 200MB)');
    }

    // Baca file
    $fileContent = file_get_contents($fileTmp);
    if (!$fileContent) throw new Exception('Gagal baca file');

    // Buat boundary
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));

    // Body multipart/form-data
    $body = '--' . $boundary . "\r\n" .
            'Content-Disposition: form-data; name="reqtype"' . "\r\n\r\n" .
            'fileupload' . "\r\n" .
            '--' . $boundary . "\r\n" .
            'Content-Disposition: form-data; name="fileToUpload"; filename="' . $fileName . '"' . "\r\n" .
            'Content-Type: application/octet-stream' . "\r\n\r\n" .
            $fileContent . "\r\n" .
            '--' . $boundary . '--';

    // ========== KONFIGURASI CURL YANG DIPERBAIKI ==========
    $ch = curl_init('https://catbox.moe/user/api.php');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    
    // HEADER YANG DIPERBAIKI
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Origin: https://catbox.moe',
        'Referer: https://catbox.moe/',
        'Expect:' // Penting: menghilangkan Expect: 100-continue
    ]);

    // Eksekusi CURL
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // Cek error CURL
    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }

    // Cek HTTP response code
    if ($http_code !== 200) {
        // Coba parse response jika ada JSON
        $errorMsg = $response;
        $json = json_decode($response, true);
        if ($json && isset($json['message'])) {
            $errorMsg = $json['message'];
        }
        throw new Exception('HTTP Error: ' . $http_code . ' - ' . $errorMsg);
    }

    // Proses response
    $url = trim($response);
    
    // Cek apakah response berupa JSON
    $jsonResponse = json_decode($url, true);
    if ($jsonResponse && isset($jsonResponse['url'])) {
        $url = $jsonResponse['url'];
    }

    // Validasi URL
    if (empty($url) || !str_starts_with($url, 'https://')) {
        throw new Exception('Upload gagal: ' . $url);
    }

    // ========== RESPONSE SUKSES ==========
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'url' => $url,
            'filename' => $fileName,
            'size' => $fileSize,
            'size_format' => formatSize($fileSize)
        ]
    ]));

} catch (Exception $e) {
    // ========== RESPONSE ERROR ==========
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]));
}

// ========== FUNGSI FORMAT UKURAN ==========
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>
