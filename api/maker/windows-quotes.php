<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fake Windows Media Player - Frame Area (Rata Kiri)
// Contoh: {"text":"kenapa nyahh aku salah mulu"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks yang ingin ditampilkan

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];
set_time_limit(30);

$text = trim($_GET['text'] ?? 'kenapa nyahh aku salah mulu');

// ===============================
// BACKGROUND & FONT
// ===============================

$BACKGROUND_URL = 'https://tiyanzcode.my.id/cdn/windows.jpg';
$FONT_URL = 'https://tiyanzcode.my.id/cdn/Arial-bold.ttf';

// ===============================
// FRAME: 99,428,486,995
// ===============================
$FRAME_X1 = 99;
$FRAME_Y1 = 428;
$FRAME_X2 = 486;
$FRAME_Y2 = 995;

// ===============================
// TEXT STYLE
// ===============================

$FONT_MAX_SIZE = 76;
$FONT_MIN_SIZE = 14;
$LINE_SPACING = 1.4;
$PADDING_LEFT = 10; // Padding kiri dari frame
$TEXT_COLOR = [0, 0, 0];

// ===============================
// FUNCTIONS
// ===============================

function downloadBuffer($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 30, CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    $result = curl_exec($ch);
    if (curl_errno($ch)) { curl_close($ch); return false; }
    curl_close($ch);
    return $result;
}

try {
    if (!extension_loaded('gd')) throw new Exception('GD extension tidak aktif');

    $bgBuffer = downloadBuffer($BACKGROUND_URL);
    if (!$bgBuffer) throw new Exception('Gagal download background');
    $bg = imagecreatefromstring($bgBuffer);
    if (!$bg) throw new Exception('Gagal membaca background');

    $width = imagesx($bg); $height = imagesy($bg);
    $canvas = imagecreatetruecolor($width, $height);
    imagecopy($canvas, $bg, 0, 0, 0, 0, $width, $height);
    imagedestroy($bg);

    $fontBuffer = downloadBuffer($FONT_URL);
    if (!$fontBuffer) throw new Exception('Gagal download font');
    $fontFile = sys_get_temp_dir() . '/font_' . uniqid() . '.ttf';
    file_put_contents($fontFile, $fontBuffer);

    $textColor = imagecolorallocate($canvas, $TEXT_COLOR[0], $TEXT_COLOR[1], $TEXT_COLOR[2]);

    $words = preg_split('/\s+/', trim($text));
    
    // Area frame
    $frameW = $FRAME_X2 - $FRAME_X1 - ($PADDING_LEFT * 2);
    $frameH = $FRAME_Y2 - $FRAME_Y1;
    
    // Auto-fit font size
    $fontSize = $FONT_MAX_SIZE;
    while ($fontSize >= $FONT_MIN_SIZE) {
        $lineHeight = $fontSize * $LINE_SPACING;
        $totalHeight = count($words) * $lineHeight;
        $widest = 0;
        foreach ($words as $word) {
            $box = imagettfbbox($fontSize, 0, $fontFile, $word);
            $tw = abs($box[2] - $box[0]);
            if ($tw > $widest) $widest = $tw;
        }
        if ($widest <= $frameW && $totalHeight <= $frameH) break;
        $fontSize -= 2;
    }

    // Render rata kiri di dalam frame
    $lineHeight = $fontSize * $LINE_SPACING;
    $startY = $FRAME_Y1 + ($frameH - (count($words) * $lineHeight)) / 2 + $fontSize;
    $textX = $FRAME_X1 + $PADDING_LEFT;
    
    foreach ($words as $i => $word) {
        $y = $startY + ($i * $lineHeight);
        imagettftext($canvas, $fontSize, 0, (int)$textX, (int)$y, $textColor, $fontFile, $word);
    }

    @unlink($fontFile);
    imagepng($canvas);
    imagedestroy($canvas);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>