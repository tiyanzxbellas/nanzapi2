<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Pixel Art Generator (Upload ke Cloudinary)
// Contoh: {"file": "foto.jpg", "level": "30"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param level (1|5|10|15|20|25|30|35|40) Level pixelasi

set_time_limit(120);

$credit = 'Nanzz';
$pixelLevel = $_REQUEST['level'] ?? 30;
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
$imageUrl = $_REQUEST['url'] ?? '';

function getBlock($level) {
    $value = min(max((int)$level, 1), 40);
    return 41 - $value;
}

function uploadToCloudinary($fileTmp, $fileName, $fileMime) {
    define('SIGN_API', 'https://cloudinary-tools.netlify.app/.netlify/functions/sign-upload-params');
    define('UPLOAD_API', 'https://api.cloudinary.com/v1_1/dtz0urit6/auto/upload');
    define('API_KEY', '985946268373735');
    define('UPLOAD_PRESET', 'cloudinary-tools');
    define('SOURCE', 'ml');
    define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36');
    
    // Get signature
    $timestamp = time();
    $ch = curl_init(SIGN_API);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'paramsToSign' => ['timestamp' => $timestamp, 'upload_preset' => UPLOAD_PRESET, 'source' => SOURCE]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'User-Agent: ' . UA]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $signRes = curl_exec($ch);
    curl_close($ch);
    $signData = json_decode($signRes, true);
    $signature = $signData['signature'] ?? '';
    if (empty($signature)) return '';
    
    // Upload
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="upload_preset"' . "\r\n\r\n" . UPLOAD_PRESET . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="source"' . "\r\n\r\n" . SOURCE . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="signature"' . "\r\n\r\n" . $signature . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="timestamp"' . "\r\n\r\n" . $timestamp . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="api_key"' . "\r\n\r\n" . API_KEY . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="file"; filename="' . $fileName . '"' . "\r\n";
    $body .= 'Content-Type: ' . $fileMime . "\r\n\r\n" . file_get_contents($fileTmp) . "\r\n";
    $body .= '--' . $boundary . '--';
    
    $ch = curl_init(UPLOAD_API);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'User-Agent: ' . UA
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $uploadRes = curl_exec($ch);
    curl_close($ch);
    $uploadData = json_decode($uploadRes, true);
    return $uploadData['secure_url'] ?? $uploadData['url'] ?? '';
}

function downloadImage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

try {
    // Dapetin URL gambar (upload ke Cloudinary atau dari URL)
    if ($hasFile) {
        $imgUrl = uploadToCloudinary($_FILES['file']['tmp_name'], $_FILES['file']['name'], mime_content_type($_FILES['file']['tmp_name']));
        if (empty($imgUrl)) throw new Exception("Upload ke Cloudinary gagal");
    } elseif (!empty($imageUrl)) {
        $imgUrl = $imageUrl;
    } else {
        throw new Exception("Upload file atau masukkan URL");
    }
    
    // Download gambar
    $imgData = downloadImage($imgUrl);
    if (!$imgData) throw new Exception("Gagal download gambar");
    
    // Pixel art
    $img = @imagecreatefromstring($imgData);
    if (!$img) throw new Exception("Format gambar tidak valid");
    
    $width = imagesx($img);
    $height = imagesy($img);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    
    $block = getBlock($pixelLevel);
    
    for ($y = 0; $y < $height; $y += $block) {
        for ($x = 0; $x < $width; $x += $block) {
            $r = 0; $g = 0; $b = 0; $a = 0; $count = 0;
            $maxY = min($y + $block, $height);
            $maxX = min($x + $block, $width);
            
            for ($yy = $y; $yy < $maxY; $yy++) {
                for ($xx = $x; $xx < $maxX; $xx++) {
                    $rgbaIndex = imagecolorat($img, $xx, $yy);
                    $r += ($rgbaIndex >> 16) & 0xFF;
                    $g += ($rgbaIndex >> 8) & 0xFF;
                    $b += $rgbaIndex & 0xFF;
                    $a += ($rgbaIndex >> 24) & 0x7F;
                    $count++;
                }
            }
            
            $r = round($r / $count); $g = round($g / $count); $b = round($b / $count); $a = round($a / $count);
            $pixelColor = imagecolorallocatealpha($img, $r, $g, $b, $a);
            
            for ($yy = $y; $yy < $maxY; $yy++) {
                for ($xx = $x; $xx < $maxX; $xx++) {
                    imagesetpixel($img, $xx, $yy, $pixelColor);
                }
            }
        }
    }
    
    // Output
    header("Content-Type: image/png");
    header("X-Creator: " . $credit);
    header("X-Original-URL: " . $imgUrl);
    imagepng($img, null, 9);
    imagedestroy($img);
    
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode(["creator" => $credit, "status" => false, "error" => $e->getMessage()]);
}
?>