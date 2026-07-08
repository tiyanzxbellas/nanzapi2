<?php
error_reporting(0);
ini_set('display_errors', '0');

/**
 * ───「 FEATURE AUTHOR 」───
 * 👤 Author     : Lynx Decode
 * 📞 Contact    : +62 882-5804-1396
 * 📢 Channel    : https://whatsapp.com/channel/0029VbAnuii6GcGCu73oep1i
 * ─────────────────────────
 * 📝 Plugin: Fake Developer 3 Maker (Support Upload File)
 * 📌 Dikonversi ke PHP oleh Tiyanz
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz', 'original' => 'Lynx Decode'];

// Ambil parameter text (bisa dari GET atau POST)
$text = trim($_GET['text'] ?? $_POST['text'] ?? '');

// Validasi text
if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Tiyanz', 
        'msg' => 'Parameter text diperlukan!'
    ]);
    exit;
}

// Fungsi upload gambar ke hosting (biar dapet URL)
function uploadImageToHost($filePath, $mimeType) {
    // Pake Litterbox dulu
    $url = 'https://api.shinzu.web.id/api/upload/litterbox';
    
    $postData = [
        'file' => new CURLFile($filePath, $mimeType, 'upload_image')
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $json = json_decode($response, true);
        if ($json && isset($json['result']['url'])) {
            return $json['result']['url'];
        }
    }
    
    return null;
}

// Cek apakah ada file yang diupload
$imageUrl = '';

// 1. Cek dari upload file (POST dengan enctype multipart/form-data)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tempPath = $_FILES['image']['tmp_name'];
    $mimeType = $_FILES['image']['type'] ?: mime_content_type($tempPath);
    
    // Upload ke hosting dulu
    $uploadedUrl = uploadImageToHost($tempPath, $mimeType);
    if ($uploadedUrl) {
        $imageUrl = $uploadedUrl;
    }
}

// 2. Cek dari parameter GET/POST (URL atau base64)
if (empty($imageUrl)) {
    $imageParam = trim($_GET['image'] ?? $_POST['image'] ?? '');
    if (!empty($imageParam)) {
        $imageUrl = $imageParam;
    }
}

// 3. Jika masih kosong, pake default
if (empty($imageUrl)) {
    $imageUrl = 'https://i.ibb.co/1s8T3sY/48f7ce63c7aa.jpg';
}

// Forward ke API JagoanProject
$apiUrl = 'https://restapi.jagoanproject.web.id/api/maker/fakedev3?' . http_build_query([
    'text' => $text,
    'verified' => 'true',
    'image' => $imageUrl
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => [
        'Accept: image/png,application/json',
        'Authorization: Bearer Lynxdecode'
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Tiyanz', 
        'msg' => 'Gagal mengambil data dari API', 
        'http_code' => $httpCode
    ]);
    exit;
}

// Cek apakah response berupa JSON
if (strpos($contentType, 'application/json') !== false) {
    $jsonData = json_decode($response, true);
    if ($jsonData && isset($jsonData['result']['url'])) {
        $imageUrl = $jsonData['result']['url'];
        $ch2 = curl_init($imageUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $imageData = curl_exec($ch2);
        $imageHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $imageContentType = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
        curl_close($ch2);
        
        if ($imageHttpCode === 200 && !empty($imageData)) {
            header('Content-Type: ' . ($imageContentType ?: 'image/png'));
            echo $imageData;
            exit;
        }
    }
}

// Jika response langsung berupa gambar
if (strpos($contentType, 'image') !== false || strpos($contentType, 'application/octet-stream') !== false) {
    header('Content-Type: ' . ($contentType ?: 'image/png'));
    echo $response;
    exit;
}

// Fallback
header('Content-Type: application/json');
echo json_encode([
    'status' => false,
    'creator' => 'Tiyanz',
    'msg' => 'Gagal memproses gambar',
    'content_type' => $contentType
]);
?>
