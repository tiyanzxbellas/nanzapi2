<?php
error_reporting(0);
ini_set('display_errors', '0');
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

    // ========== UPLOAD KE CATBOX ==========
    $postData = [
        'reqtype' => 'fileupload',
        'fileToUpload' => new CURLFile($fileTmp, mime_content_type($fileTmp), $fileName)
    ];

    $ch = curl_init('https://catbox.moe/user/api.php');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // HEADER LENGKAP
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.9',
        'Origin: https://catbox.moe',
        'Referer: https://catbox.moe/',
        'Content-Type: multipart/form-data'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }

    // Response 412 biasanya karena CURLFile bermasalah
    if ($http_code === 412) {
        // COBA METHOD ALTERNATIF: PAKAI @ (deprecated tapi kadang work)
        $ch = curl_init('https://catbox.moe/user/api.php');
        
        $postDataAlt = [
            'reqtype' => 'fileupload',
            'fileToUpload' => "@$fileTmp",
            'name' => $fileName
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataAlt);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Origin: https://catbox.moe',
            'Referer: https://catbox.moe/'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error (alt): ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('HTTP Error (alt): ' . $http_code . ' - ' . $response);
        }
    } elseif ($http_code !== 200) {
        throw new Exception('HTTP Error: ' . $http_code . ' - ' . $response);
    }

    $url = trim($response);
    
    // Validasi response
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Upload gagal: ' . $url);
    }

    if (strpos(strtolower($url), 'error') !== false || strpos($url, 'http') === false) {
        throw new Exception('Catbox Error: ' . $url);
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
