<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: YouTube Play Button Generator
// Contoh: {"nama":"Nanzz","template":"silver"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param template (silver|gold) Template
// @param nama Nama Channel

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$nama = $_GET['nama'] ?? 'Nanzz';
$template = $_GET['template'] ?? 'silver';

// ==================== SHORTCUT ====================
// SILVER
$silverFontSize = 60;
$silverOffsetX = 850;
$silverOffsetY = 1099;

// GOLD
$goldFontSize = 79;
$goldOffsetX = 765;
$goldOffsetY = 1290;

// WARNA TEKS (R, G, B)
$textColorR = 240;
$textColorG = 240;
$textColorB = 240;
// ==================================================

if ($template === 'gold') {
    $bgUrl = 'https://nanzzcode.my.id/play-button/gold.jpg';
    $frameX1 = 148; $frameY1 = 423; $frameX2 = 365; $frameY2 = 439;
    $fontSize = $goldFontSize; $offsetX = $goldOffsetX; $offsetY = $goldOffsetY;
} else {
    $bgUrl = 'https://nanzzcode.my.id/play-button/silver.jpg';
    $frameX1 = 179; $frameY1 = 360; $frameX2 = 378; $frameY2 = 373;
    $fontSize = $silverFontSize; $offsetX = $silverOffsetX; $offsetY = $silverOffsetY;
}

$fontUrl = 'https://raw.githubusercontent.com/SaurusAraAra/mentahan/main/font/Poppins.ttf';

function downloadBuffer($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
    ]);
    $data = curl_exec($ch); curl_close($ch);
    return $data;
}

try {
    $bgBuffer = downloadBuffer($bgUrl);
    $fontBuffer = downloadBuffer($fontUrl);
    
    $bg = imagecreatefromstring($bgBuffer);
    if (!$bg) throw new Exception('Gagal load background');
    
    $canvas = imagecreatetruecolor(imagesx($bg), imagesy($bg));
    imagecopy($canvas, $bg, 0, 0, 0, 0, imagesx($bg), imagesy($bg));
    imagedestroy($bg);
    
    $fontFile = sys_get_temp_dir() . '/playbtn_' . uniqid() . '.ttf';
    file_put_contents($fontFile, $fontBuffer);
    
    $areaW = $frameX2 - $frameX1 - 10;
    $areaH = $frameY2 - $frameY1;
    
    $box = imagettfbbox($fontSize, 0, $fontFile, $nama);
    $textW = abs($box[2] - $box[0]);
    $textH = abs($box[7] - $box[1]);
    
    // PASTIKAN PAKE $textColor, BUKAN $white
    $textColor = imagecolorallocate($canvas, $textColorR, $textColorG, $textColorB);
    
    $x = $frameX1 + ($areaW - $textW) / 2 + $offsetX;
    $y = $frameY1 + ($areaH - $textH) / 2 + $fontSize + $offsetY;
    
    imagettftext($canvas, $fontSize, 0, (int)$x, (int)$y, $textColor, $fontFile, $nama);
    
    @unlink($fontFile);
    imagejpeg($canvas, null, 95);
    imagedestroy($canvas);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Nanzz', 'status' => false, 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>