<?php
error_reporting(0);
ini_set('display_errors', '0');

/**
 * ───「 FEATURE AUTHOR 」───
 * 👤 Author     : Lynx Decode
 * 📞 Contact    : +62 882-5804-1396
 * 📢 Channel    : https://whatsapp.com/channel/0029VbAnuii6GcGCu73oep1i
 * ⚠️ Note       : Keep credit to respect the creator!
 * ─────────────────────────
 * 📝 Plugin: Fake Developer 3 Maker
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

// Ambil parameter text
$text = trim($_GET['text'] ?? $_POST['text'] ?? '');

// Validasi text
if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Tiyanz', 
        'msg' => "┌˚₊ ๑│ ғ ᴀ ᴋ ᴇ  ᴅ ᴇ ᴠ  3 │๑˚₊ ⚠️\n" .
                 "┇ \n" .
                 "│ ❌ Format salah!\n" .
                 "│ \n" .
                 "│ 📌 Cara pakai:\n" .
                 "│ GET/POST ?text=<teks>&image=<url>\n" .
                 "│ \n" .
                 "│ 💡 Tips: Kirim/reply foto dengan caption command ini,\n" .
                 "│ jika tidak membalas foto, akan menggunakan PP default.\n" .
                 "┇ \n" .
                 "└˚₊ ๑ ────────────── ๑˚₊\n" .
                 "> © ERINE-AI"
    ]);
    exit;
}

// Fungsi upload ke Litterbox
function uploadToLitterbox($filePath, $mimeType) {
    $url = 'https://api.shinzu.web.id/api/upload/litterbox';
    
    $postData = [
        'file' => new CURLFile($filePath, $mimeType, 'upload_file')
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
        if ($json && isset($json['status']) && $json['status'] && isset($json['result']['url'])) {
            return $json['result']['url'];
        }
    }
    return null;
}

// Proses image
$imageUrl = '';

// 1. Cek upload file
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tempPath = $_FILES['image']['tmp_name'];
    $mimeType = $_FILES['image']['type'] ?: mime_content_type($tempPath);
    
    if (strpos($mimeType, 'image') !== false) {
        $uploadedUrl = uploadToLitterbox($tempPath, $mimeType);
        if ($uploadedUrl) {
            $imageUrl = $uploadedUrl;
        }
    }
}

// 2. Cek parameter image (URL)
if (empty($imageUrl)) {
    $imageParam = trim($_GET['image'] ?? $_POST['image'] ?? '');
    if (!empty($imageParam)) {
        $imageUrl = $imageParam;
    }
}

// 3. Default profile picture
if (empty($imageUrl)) {
    $imageUrl = 'https://i.ibb.co/1s8T3sY/48f7ce63c7aa.jpg';
}

// Panggil API JagoanProject
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

// Proses response
if (strpos($contentType, 'application/json') !== false) {
    $jsonData = json_decode($response, true);
    
    if (!$jsonData || !isset($jsonData['status']) || !$jsonData['status']) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => false,
            'creator' => 'Tiyanz',
            'msg' => $jsonData['message'] ?? 'API mengembalikan error'
        ]);
        exit;
    }
    
    $resultUrl = $jsonData['result']['url'] ?? $jsonData['data']['result']['url'] ?? null;
    
    if (!$resultUrl) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => false,
            'creator' => 'Tiyanz',
            'msg' => 'Respon JSON API tidak sesuai',
            'data' => $jsonData
        ]);
        exit;
    }
    
    // Download gambar dari URL
    $ch2 = curl_init($resultUrl);
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

// Jika response langsung gambar
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
