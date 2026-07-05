<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: NASA Landsat Name Generator
// Contoh: {"name":"Nanas"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param name Nama

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(60);

$name = $_GET['name'] ?? 'NASA';
$name = strtolower(preg_replace('/[^a-z]/', '', $name));

if (!$name) {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter name diperlukan (huruf A-Z)']), JSON_PRETTY_PRINT);
    exit;
}

define('BASE_URL', 'https://science.nasa.gov/specials/your-name-in-landsat/images');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36');

function downloadImage($letter) {
    $url = BASE_URL . '/' . $letter . '_0.jpg';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: ' . UA,
            'Referer: https://science.nasa.gov/specials/your-name-in-landsat/',
            'Accept: image/*'
        ]
    ]);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$data) return null;
    return $data;
}

$letters = [];
$totalWidth = 0;
$letterW = 250;
$letterH = 600;
$gap = 10;

// Download semua huruf
for ($i = 0; $i < strlen($name); $i++) {
    $letter = $name[$i];
    $imgData = downloadImage($letter);
    
    if (!$imgData) {
        header('Content-Type: application/json');
        echo json_encode(array_merge($credit, ['status' => false, 'message' => "Gagal download huruf: {$letter}"]), JSON_PRETTY_PRINT);
        exit;
    }
    
    $img = imagecreatefromstring($imgData);
    if (!$img) continue;
    
    // Resize ke 250x600
    $resized = imagecreatetruecolor($letterW, $letterH);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $letterW, $letterH, imagesx($img), imagesy($img));
    imagedestroy($img);
    
    $letters[] = $resized;
    $totalWidth += $letterW + $gap;
}

$totalWidth -= $gap;
if ($totalWidth < 0) $totalWidth = $letterW;

// Bikin canvas final
$canvas = imagecreatetruecolor($totalWidth, $letterH);
$black = imagecolorallocate($canvas, 0, 0, 0);
imagefill($canvas, 0, 0, $black);

// Tempel semua huruf
$x = 0;
foreach ($letters as $img) {
    imagecopy($canvas, $img, $x, 0, 0, 0, $letterW, $letterH);
    imagedestroy($img);
    $x += $letterW + $gap;
}

imagejpeg($canvas, null, 95);
imagedestroy($canvas);
?>