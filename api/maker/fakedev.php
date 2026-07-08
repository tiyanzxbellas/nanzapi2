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

header('Content-Type: image/png; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz', 'original' => 'Lynx Decode'];

// Ambil parameter
$text   = trim($_GET['text'] ?? '');
$image  = trim($_GET['image'] ?? '');

// Validasi parameter
if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Tiyanz', 
        'msg' => 'Parameter text diperlukan! Contoh: ?text=Hello+World&image=https://example.com/foto.jpg'
    ]);
    exit;
}

if (empty($image)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Tiyanz', 
        'msg' => 'Parameter image (URL foto) diperlukan!'
    ]);
    exit;
}

// Forward ke API asli JagoanProject
$apiUrl = 'https://restapi.jagoanproject.web.id/api/maker/fakedev3?' . http_build_query([
    'text' => $text,
    'verified' => 'true',
    'image' => $image
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

// Cek apakah response berupa JSON (jika API mengembalikan error)
if (strpos($contentType, 'application/json') !== false) {
    $jsonData = json_decode($response, true);
    if ($jsonData && isset($jsonData['status']) && $jsonData['status'] === false) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => false,
            'creator' => 'Tiyanz',
            'msg' => $jsonData['message'] ?? 'API mengembalikan error'
        ]);
        exit;
    }
    
    // Jika response JSON dengan URL gambar
    if ($jsonData && isset($jsonData['result']['url'])) {
        // Ambil gambar dari URL yang diberikan
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

// Fallback: jika semua gagal
header('Content-Type: application/json');
echo json_encode([
    'status' => false,
    'creator' => 'Tiyanz',
    'msg' => 'Gagal memproses gambar',
    'content_type' => $contentType
]);
?>
