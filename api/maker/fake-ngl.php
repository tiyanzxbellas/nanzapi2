<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fake NGL Generator
// Contoh: {"text":"Gw tuh sebenarnya ultramen"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks Pesan

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];
set_time_limit(60);

$text = $_GET['text'] ?? 'Gw tuh sebenarnya ultramen';

// ==================== SHORTCUT ====================
// FRAME (280,1032,2271,1754)
$frameX1 = 280;
$frameY1 = 1032;
$frameX2 = 2271;
$frameY2 = 1754;

$fontMax = 100;
$fontMin = 37;
$fontPadding = 30;
$offsetX = 0;
$offsetY = 50;

$textColorR = 0;
$textColorG = 0;
$textColorB = 0;
// ==================================================

$bgUrl = 'https://www.gobox.my.id/file/jojYN.jpg';
$fontUrl = 'https://raw.githubusercontent.com/google/fonts/main/ofl/poppins/Poppins-SemiBold.ttf';

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

function measureTextWidth($text, $fontSize, $fontFile) {
    $box = imagettfbbox($fontSize, 0, $fontFile, $text);
    return abs($box[2] - $box[0]);
}

function wrapText($text, $maxWidth, $fontSize, $fontFile) {
    $words = explode(' ', $text);
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $test = $line ? $line . ' ' . $word : $word;
        if (measureTextWidth($test, $fontSize, $fontFile) > $maxWidth) {
            if ($line) $lines[] = $line;
            $line = $word;
        } else {
            $line = $test;
        }
    }
    if ($line) $lines[] = $line;
    return $lines;
}

try {
    $imageBuffer = downloadBuffer($bgUrl);
    $fontBuffer = downloadBuffer($fontUrl);
    
    $fontFile = sys_get_temp_dir() . '/poppins_' . uniqid() . '.ttf';
    file_put_contents($fontFile, $fontBuffer);
    
    $image = imagecreatefromstring($imageBuffer);
    if (!$image) throw new Exception('Gagal membuka template');
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    $canvas = imagecreatetruecolor($width, $height);
    imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
    imagedestroy($image);
    
    $areaW = $frameX2 - $frameX1 - ($fontPadding * 2);
    $areaH = $frameY2 - $frameY1 - ($fontPadding * 2);
    
    $fontSize = $fontMax;
    $lines = [];
    
    while ($fontSize >= $fontMin) {
        $lines = wrapText($text, $areaW, $fontSize, $fontFile);
        $lineHeight = $fontSize * 1.25;
        $totalHeight = count($lines) * $lineHeight;
        $widestLine = 0;
        foreach ($lines as $line) {
            $widestLine = max($widestLine, measureTextWidth($line, $fontSize, $fontFile));
        }
        if ($widestLine <= $areaW && $totalHeight <= $areaH) break;
        $fontSize--;
    }
    
    $textColor = imagecolorallocate($canvas, $textColorR, $textColorG, $textColorB);
    $lineHeight = $fontSize * 1.25;
    $startY = $frameY1 + ($areaH - (count($lines) * $lineHeight)) / 2 + ($lineHeight / 2) + $offsetY;
    
    foreach ($lines as $index => $line) {
        $lineWidth = measureTextWidth($line, $fontSize, $fontFile);
        $x = $frameX1 + ($areaW - $lineWidth) / 2 + $offsetX;
        $y = $startY + ($index * $lineHeight);
        imagettftext($canvas, $fontSize, 0, (int)$x, (int)$y, $textColor, $fontFile, $line);
    }
    
    imagejpeg($canvas, null, 95);
    imagedestroy($canvas);
    @unlink($fontFile);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>