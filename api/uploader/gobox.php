<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Gobox.my.id Upload File MAX 4,5MB
// Contoh: Kirim file lewat form-data dengan key "file"

set_time_limit(120);
header("Content-Type: application/json; charset=UTF-8");

$API = "https://www.gobox.my.id/upload";

try {
    // 1. Validasi file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['file']['error'] ?? 'NO_FILE';
        throw new Exception("Kamu belum masukin file! (Error Code: " . $errorCode . ")");
    }

    // 2. Ambil info file
    $tmpFilePath = $_FILES['file']['tmp_name'];
    $fileName    = $_FILES['file']['name'];
    $fileType    = $_FILES['file']['type'] ?: 'application/octet-stream';

    // 3. Siapkan CURLFile
    $cfile = new CURLFile($tmpFilePath, $fileType, $fileName);

    // 4. Payload multipart
    $payload = [
        'file' => $cfile
    ];

    // 5. Kirim ke Gobox
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $API,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Origin: https://www.gobox.my.id',
            'Referer: https://www.gobox.my.id/'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("cURL Error: " . $curlErr);
    }

    // 6. Parse response (coba JSON dulu, fallback ke teks)
    $data = json_decode($response, true);
    $responseText = trim($response);

    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        // Response JSON
        $success = !empty($data['url']) || !empty($data['file']) || ($data['status'] ?? '') === 'success';
        $resultUrl = $data['url'] ?? $data['file'] ?? $data['link'] ?? $responseText;
        
        echo json_encode([
            "Status"     => $success,
            "Code"       => $httpCode,
            "Input_Name" => $fileName,
            "Result_url" => $resultUrl,
            "Raw"        => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        // Response teks biasa
        $isSuccessUrl = (strpos($responseText, 'https://') === 0);
        $resultUrl = $isSuccessUrl ? $responseText : null;

        echo json_encode([
            "Status"     => ($httpCode === 200 && !empty($resultUrl)),
            "Code"       => $httpCode,
            "Input_Name" => $fileName,
            "Result_url" => $resultUrl ?: $responseText
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    echo json_encode([
        "Status"     => false,
        "Code"       => 400,
        "Result_url" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>