<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Wasted Effect - GTA V Wasted style
// Contoh: {"url":"https://www.upload.ee/image/19400325/images.webp"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL Gambar

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$imageUrl = $_GET['url'] ?? '';

if (!$imageUrl) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Parameter url diperlukan'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$wastedUrl = 'https://www.gobox.my.id/file/0bsEg.jpg';

function downloadBuffer($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

try {
    $imgBuffer = downloadBuffer($imageUrl);
    $wastedBuffer = downloadBuffer($wastedUrl);
    
    if (!$imgBuffer) throw new Exception('Gagal load gambar');
    if (!$wastedBuffer) throw new Exception('Gagal load wasted overlay');
    
    $image = imagecreatefromstring($imgBuffer);
    $wasted = imagecreatefromstring($wastedBuffer);
    
    if (!$image) throw new Exception('Gagal decode gambar');
    if (!$wasted) throw new Exception('Gagal decode overlay');
    
    $imgW = imagesx($image);
    $imgH = imagesy($image);
    
    // Grayscale
    imagefilter($image, IMG_FILTER_GRAYSCALE);
    
    // Resize wasted ke 80% lebar
    $wastedW = imagesx($wasted);
    $wastedH = imagesy($wasted);
    $targetW = floor($imgW * 0.8);
    $targetH = floor($wastedH * ($targetW / $wastedW));
    
    $wastedResized = imagecreatetruecolor($targetW, $targetH);
    imagealphablending($wastedResized, false);
    imagesavealpha($wastedResized, true);
    $transparent = imagecolorallocatealpha($wastedResized, 0, 0, 0, 127);
    imagefill($wastedResized, 0, 0, $transparent);
    imagecopyresampled($wastedResized, $wasted, 0, 0, 0, 0, $targetW, $targetH, $wastedW, $wastedH);
    
    // Posisi tengah
    $textX = floor(($imgW - $targetW) / 2);
    $textY = floor(($imgH - $targetH) / 2);
    
    // Composite
    imagealphablending($image, true);
    imagecopy($image, $wastedResized, (int)$textX, (int)$textY, 0, 0, $targetW, $targetH);
    
    imagepng($image);
    imagedestroy($image);
    imagedestroy($wasted);
    imagedestroy($wastedResized);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>