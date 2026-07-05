<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tanya Ustadz Generator
// Contoh: {"text":"Apakah makan mie tiap hari itu sehat?"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks pertanyaan

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$text = trim($_GET['text'] ?? 'halo');

// ===============================
// BACKGROUND & FONT
// ===============================

$BACKGROUND_URL = 'https://nanzzcode.my.id/tanyaustadz.jpg';
$FONT_URL       = 'https://nanzzcode.my.id/Roboto.ttf';

// ===============================
// TEXT AREA
// ===============================

$TEXT_LEFT   = 115;
$TEXT_TOP    = 353;
$TEXT_RIGHT  = 873;
$TEXT_BOTTOM = 630;

$MAX_FONT_SIZE = 50;
$MIN_FONT_SIZE = 22;

$TEXT_COLOR = [0,0,0];

// ===============================
// SHADOW
// ===============================

$SHADOW_COLOR = [255,255,255];
$SHADOW_X = 0;
$SHADOW_Y = 0;

function downloadBuffer($url)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    return $result;
}

function wrapText($font, $size, $text, $maxWidth)
{
    $words = explode(' ', $text);

    $lines = [];
    $current = '';

    foreach ($words as $word) {

        $test = trim($current . ' ' . $word);

        $box = imagettfbbox($size, 0, $font, $test);
        $width = abs($box[2] - $box[0]);

        if ($width > $maxWidth && $current != '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $test;
        }
    }

    if ($current != '') {
        $lines[] = $current;
    }

    return $lines;
}

try {

    if (!extension_loaded('gd')) {
        throw new Exception('GD extension tidak aktif');
    }

    $bgBuffer = downloadBuffer($BACKGROUND_URL);

    if (!$bgBuffer) {
        throw new Exception('Gagal download background');
    }

    $bg = imagecreatefromstring($bgBuffer);

    if (!$bg) {
        throw new Exception('Gagal membaca gambar');
    }

    $width = imagesx($bg);
    $height = imagesy($bg);

    $canvas = imagecreatetruecolor($width, $height);

    imagecopy(
        $canvas,
        $bg,
        0,
        0,
        0,
        0,
        $width,
        $height
    );

    imagedestroy($bg);

    $fontFile = sys_get_temp_dir() . '/font_' . uniqid() . '.ttf';

    $fontBuffer = downloadBuffer($FONT_URL);

    if (!$fontBuffer) {
        throw new Exception('Gagal download font');
    }

    file_put_contents($fontFile, $fontBuffer);

    $areaWidth = $TEXT_RIGHT - $TEXT_LEFT;
    $areaHeight = $TEXT_BOTTOM - $TEXT_TOP;

    $fontSize = $MAX_FONT_SIZE;

    while ($fontSize >= $MIN_FONT_SIZE) {

        $lines = wrapText(
            $fontFile,
            $fontSize,
            $text,
            $areaWidth - 20
        );

        $lineHeight = $fontSize + 12;
        $totalHeight = count($lines) * $lineHeight;

        if ($totalHeight <= $areaHeight) {
            break;
        }

        $fontSize--;
    }

    $textColor = imagecolorallocate(
        $canvas,
        $TEXT_COLOR[0],
        $TEXT_COLOR[1],
        $TEXT_COLOR[2]
    );

    $shadowColor = imagecolorallocate(
        $canvas,
        $SHADOW_COLOR[0],
        $SHADOW_COLOR[1],
        $SHADOW_COLOR[2]
    );

    $lineHeight = $fontSize + 12;
    $totalHeight = count($lines) * $lineHeight;

    $startY = $TEXT_TOP + (($areaHeight - $totalHeight) / 2) + $fontSize;

    foreach ($lines as $i => $line) {

        $bbox = imagettfbbox(
            $fontSize,
            0,
            $fontFile,
            $line
        );

        $lineWidth = abs($bbox[2] - $bbox[0]);

        $x = $TEXT_LEFT + (($areaWidth - $lineWidth) / 2);

        $y = $startY + ($i * $lineHeight);

        imagettftext(
            $canvas,
            $fontSize,
            0,
            (int)$x + $SHADOW_X,
            (int)$y + $SHADOW_Y,
            $shadowColor,
            $fontFile,
            $line
        );

        imagettftext(
            $canvas,
            $fontSize,
            0,
            (int)$x,
            (int)$y,
            $textColor,
            $fontFile,
            $line
        );
    }

    @unlink($fontFile);

    imagepng($canvas);
    imagedestroy($canvas);

} catch (Exception $e) {

    header('Content-Type: application/json');

    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>