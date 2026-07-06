<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: NASA Certificate Generator
// Contoh: {"nama":"Nanas Bocil "}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Nama yang akan ditampilkan

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$nama = trim($_GET['nama'] ?? '');

if (!$nama) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Parameter nama diperlukan'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$backgroundUrl = 'https://tiyanzcode.my.id/nasa.png';
$fontUrl       = 'https://tiyanzcode.my.id/CormorantGaramond-SemiBold.ttf';

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

try {

    if (!extension_loaded('gd')) {
        throw new Exception('GD extension tidak aktif');
    }

    if (!function_exists('imagettftext')) {
        throw new Exception('GD FreeType tidak aktif');
    }

    // Background
    $bgBuffer = downloadBuffer($backgroundUrl);

    if (!$bgBuffer) {
        throw new Exception('Gagal download background');
    }

    $bg = imagecreatefromstring($bgBuffer);

    if (!$bg) {
        throw new Exception('Gagal membaca background');
    }

    $canvas = imagecreatetruecolor(
        imagesx($bg),
        imagesy($bg)
    );

    imagecopy(
        $canvas,
        $bg,
        0,
        0,
        0,
        0,
        imagesx($bg),
        imagesy($bg)
    );

    imagedestroy($bg);

    // Font
    $fontFile = sys_get_temp_dir() . '/font_' . uniqid() . '.ttf';

    $fontBuffer = downloadBuffer($fontUrl);

    if (!$fontBuffer) {
        throw new Exception('Gagal download font');
    }

    file_put_contents($fontFile, $fontBuffer);

    // Area nama
    $left   = 36;
    $top    = 557;
    $right  = 984;
    $bottom = 692;

    $boxWidth  = $right - $left;
    $boxHeight = $bottom - $top;

    // Cari ukuran font otomatis
    $fontSize = 82;

    while ($fontSize > 20) {

        $bbox = imagettfbbox(
            $fontSize,
            0,
            $fontFile,
            $nama
        );

        $textWidth = abs($bbox[2] - $bbox[0]);

        if ($textWidth <= ($boxWidth - 40)) {
            break;
        }

        $fontSize--;
    }

    $bbox = imagettfbbox(
        $fontSize,
        0,
        $fontFile,
        $nama
    );

    $textWidth  = abs($bbox[2] - $bbox[0]);
    $textHeight = abs($bbox[7] - $bbox[1]);

    $x = $left + (($boxWidth - $textWidth) / 2);

    // Sedikit turun supaya mirip contoh
    $y = $top + (($boxHeight + $textHeight) / 2) + 12;

    // ===== WARNA EMAS MIRIP CONTOH =====

    $shadow = imagecolorallocate(
        $canvas,
        55,
        30,
        8
    );

    $goldDark = imagecolorallocate(
        $canvas,
        120,
        78,
        32
    );

    $gold = imagecolorallocate(
        $canvas,
        183,
        136,
        73
    );

    $highlight = imagecolorallocate(
        $canvas,
        215,
        170,
        100
    );

    // ===== RENDER TEXT =====

    // Shadow
    imagettftext(
        $canvas,
        $fontSize,
        0,
        (int)$x + 3,
        (int)$y + 3,
        $shadow,
        $fontFile,
        $nama
    );

    // Depth bawah
    imagettftext(
        $canvas,
        $fontSize,
        0,
        (int)$x,
        (int)$y + 2,
        $goldDark,
        $fontFile,
        $nama
    );

    // Gold utama
    imagettftext(
        $canvas,
        $fontSize,
        0,
        (int)$x,
        (int)$y,
        $gold,
        $fontFile,
        $nama
    );

    // Highlight tipis
    imagettftext(
        $canvas,
        $fontSize,
        0,
        (int)$x,
        (int)$y - 1,
        $highlight,
        $fontFile,
        $nama
    );

    @unlink($fontFile);

    imagepng($canvas);
    imagedestroy($canvas);

} catch (Exception $e) {

    header('Content-Type: application/json');

    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>