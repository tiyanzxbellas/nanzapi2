<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Squad Name Generator
// Contoh: {"nama1":"Nanzz","nama2":"Azzam","nama3":"Rizky","nama4":"Budi"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama1 Nama 1
// @param nama2 Nama 2
// @param nama3 Nama 3
// @param nama4 Nama 4

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$nama1 = trim($_GET['nama1'] ?? 'Nanzz');
$nama2 = trim($_GET['nama2'] ?? 'Azzam');
$nama3 = trim($_GET['nama3'] ?? 'Rizky');
$nama4 = trim($_GET['nama4'] ?? 'Budi');

// ===============================
// BACKGROUND & FONT
// ===============================

$BACKGROUND_URL = 'https://nanzzcode.my.id/squad.jpg';
$FONT_URL = 'https://nanzzcode.my.id/CormorantGaramond-SemiBold.ttf';

// ===============================
// NAMA 1
// ===============================

$NAMA1_SIZE  = 14;
$NAMA1_X     = -2;
$NAMA1_Y     = -2;
$NAMA1_COLOR = [230,190,115];

// ===============================
// NAMA 2
// ===============================

$NAMA2_SIZE  = 14;
$NAMA2_X     = -2;
$NAMA2_Y     = -2;
$NAMA2_COLOR = [230,190,115];

// ===============================
// NAMA 3
// ===============================

$NAMA3_SIZE  = 13;
$NAMA3_X     = 2;
$NAMA3_Y     = -5;
$NAMA3_COLOR = [230,190,115];

// ===============================
// NAMA 4
// ===============================

$NAMA4_SIZE  = 14;
$NAMA4_X     = 15;
$NAMA4_Y     = -2;
$NAMA4_COLOR = [255,255,255];

// ===============================
// SHADOW
// ===============================

$SHADOW_COLOR = [0,0,0];
$SHADOW_X = 1;
$SHADOW_Y = 1;

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

function drawTextLeft(
    $img,
    $font,
    $text,
    $left,
    $top,
    $right,
    $bottom,
    $size,
    $offsetX,
    $offsetY,
    $colorRGB,
    $shadowRGB,
    $shadowX,
    $shadowY
) {

    $textColor = imagecolorallocate($img,$colorRGB[0],$colorRGB[1],$colorRGB[2]);
    $shadowColor = imagecolorallocate($img,$shadowRGB[0],$shadowRGB[1],$shadowRGB[2]);

    $currentSize = $size;
    $boxWidth = $right - $left;

    do {
        $bbox = imagettfbbox($currentSize,0,$font,$text);
        $textWidth = abs($bbox[2] - $bbox[0]);

        if ($textWidth <= $boxWidth) {
            break;
        }

        $currentSize--;

    } while ($currentSize >= 8);

    $bbox = imagettfbbox($currentSize,0,$font,$text);
    $textHeight = abs($bbox[7] - $bbox[1]);

    $x = $left + $offsetX;

    $boxHeight = $bottom - $top;
    $y = $top + (($boxHeight + $textHeight) / 2) + $offsetY;

    imagettftext($img,$currentSize,0,(int)$x + $shadowX,(int)$y + $shadowY,$shadowColor,$font,$text);
    imagettftext($img,$currentSize,0,(int)$x,(int)$y,$textColor,$font,$text);
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

    file_put_contents(
        $fontFile,
        $fontBuffer
    );

    // ===============================
    // NAMA 2
    // ===============================

    drawTextLeft(
        $canvas,
        $fontFile,
        $nama2,
        140,300,210,314,
        $NAMA2_SIZE,
        $NAMA2_X,
        $NAMA2_Y,
        $NAMA2_COLOR,
        $SHADOW_COLOR,
        $SHADOW_X,
        $SHADOW_Y
    );

    // ===============================
    // NAMA 1
    // ===============================

    drawTextLeft(
        $canvas,
        $fontFile,
        $nama1,
        290,329,365,346,
        $NAMA1_SIZE,
        $NAMA1_X,
        $NAMA1_Y,
        $NAMA1_COLOR,
        $SHADOW_COLOR,
        $SHADOW_X,
        $SHADOW_Y
    );

    // ===============================
    // NAMA 3
    // ===============================

    drawTextLeft(
        $canvas,
        $fontFile,
        $nama3,
        432,300,496,315,
        $NAMA3_SIZE,
        $NAMA3_X,
        $NAMA3_Y,
        $NAMA3_COLOR,
        $SHADOW_COLOR,
        $SHADOW_X,
        $SHADOW_Y
    );

    // ===============================
    // NAMA 4
    // ===============================

    drawTextLeft(
        $canvas,
        $fontFile,
        $nama4,
        556,257,652,273,
        $NAMA4_SIZE,
        $NAMA4_X,
        $NAMA4_Y,
        $NAMA4_COLOR,
        $SHADOW_COLOR,
        $SHADOW_X,
        $SHADOW_Y
    );

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