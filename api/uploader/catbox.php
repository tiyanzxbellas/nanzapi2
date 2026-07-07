<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Deskripsi: Catbox.moe Uploader (DEBUG)
// Contoh: {"file": "pilih_file.jpg"}

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz'
];

try {
    // DEBUG: Cek apakah file diterima
    if (!isset($_FILES['file'])) {
        throw new Exception('No file received in $_FILES');
    }
    
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (max upload ini)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (max form)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder hilang',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
            UPLOAD_ERR_EXTENSION => 'Ekstensi PHP menghentikan upload'
        ];
        throw new Exception('Upload error: ' . ($errors[$_FILES['file']['error']] ?? 'Unknown error'));
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];

    // DEBUG: Cek file temporer
    if (!file_exists($fileTmp)) {
        throw new Exception('Temporary file not found: ' . $fileTmp);
    }

    if ($fileSize > 200 * 1024 * 1024) {
        throw new Exception('File terlalu besar (max 200MB)');
    }

    // ========== DEBUG: TAMPILKAN INFORMASI FILE ==========
    $debug = [
        'tmp_name' => $fileTmp,
        'name' => $fileName,
        'size' => $fileSize,
        'mime' => mime_content_type($fileTmp),
        'exists' => file_exists($fileTmp)
    ];

    // ========== UPLOAD KE CATBOX ==========
    $boundary = md5(time());
    $body = "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"reqtype\"\r\n\r\n";
    $body .= "fileupload\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"fileToUpload\"; filename=\"" . basename($fileName) . "\"\r\n";
    $body .= "Content-Type: " . mime_content_type($fileTmp) . "\r\n\r\n";
    $body .= file_get_contents($fileTmp) . "\r\n";
    $body .= "--$boundary--\r\n";

    $ch = curl_init('https://catbox.moe/user/api.php');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Origin: https://catbox.moe',
        'Referer: https://catbox.moe/'
    ]);

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
    
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Upload gagal: ' . $url);
    }

    echo json_encode(array_merge($credit, [
        'status' => true,
        'debug' => $debug,
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
        'message' => $e->getMessage(),
        'debug' => isset($debug) ? $debug : null
    ]));
}
?>
