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

    $fileContent = file_get_contents($fileTmp);
    if (!$fileContent) throw new Exception('Gagal baca file');

    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));

    $body = '--' . $boundary . "\r\n" .
            'Content-Disposition: form-data; name="reqtype"' . "\r\n\r\n" .
            'fileupload' . "\r\n" .
            '--' . $boundary . "\r\n" .
            'Content-Disposition: form-data; name="fileToUpload"; filename="' . $fileName . '"' . "\r\n" .
            'Content-Type: application/octet-stream' . "\r\n\r\n" .
            $fileContent . "\r\n" .
            '--' . $boundary . '--';

    $ch = curl_init('https://catbox.moe/user/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Accept: */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Origin: https://catbox.moe',
        'Referer: https://catbox.moe/',
        'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36',
        'X-Requested-With: XMLHttpRequest',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) throw new Exception('CURL Error: ' . $error);
    if ($http_code !== 200) throw new Exception('HTTP Error: ' . $http_code . ' - ' . substr($response, 0, 100));

    $url = trim($response);

    if (empty($url) || !str_starts_with($url, 'https://')) {
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