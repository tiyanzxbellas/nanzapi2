<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Patrick Brat Image
// Contoh: {"text":"Halo semuanya namaku nanas"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks

set_time_limit(60);

$text = $_GET['text'] ?? 'Halo semuanya namaku saurus';

define('BRAT_IMAGE_URL', 'https://raw.githubusercontent.com/SaurusAraAra/mentahan/refs/heads/main/images/brat-patrick.jpg');
define('BRAT_FONT_URL', 'https://raw.githubusercontent.com/SaurusAraAra/mentahan/main/font/Poppins.ttf');

$CANVAS = [
    'width' => 1536,
    'height' => 1536
];

$SAFE_ZONE = [
    'a' => 760,
    'b' => 1060,
    'c' => 420,
    'd' => 1130
];

$TEXT_STYLE = [
    'fontFamily' => 'Poppins',
    'maxFontSize' => 70,
    'minFontSize' => 10,
    'lineHeight' => 1.15,
    'color' => '#111111'
];

function downloadBuffer($url) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0'
        ]
    ]);

    $data = curl_exec($ch);

    curl_close($ch);

    if (!$data) {
        throw new Exception('Gagal download: ' . $url);
    }

    return $data;
}

function normalizeText($text) {
    return trim(
        preg_replace(
            '/[ \t]+/',
            ' ',
            str_replace("\r", '', (string)$text)
        )
    );
}

function getSafeRect($zone) {
    return [
        'x' => $zone['c'],
        'y' => $zone['a'],
        'w' => $zone['d'] - $zone['c'],
        'h' => $zone['b'] - $zone['a'],
        'centerX' => ($zone['c'] + $zone['d']) / 2,
        'centerY' => ($zone['a'] + $zone['b']) / 2
    ];
}

function setFontSizeDummy($size) {
    return $size;
}

function measureTextWidth($text, $fontSize, $fontFile) {
    $box = imagettfbbox($fontSize, 0, $fontFile, $text);
    return abs($box[2] - $box[0]);
}

function wrapTextCustom($text, $maxWidth, $fontSize, $fontFile) {

    $words = explode(' ', $text);

    $lines = [];

    $line = '';

    foreach ($words as $word) {

        $test = $line
            ? $line . ' ' . $word
            : $word;

        if (
            measureTextWidth(
                $test,
                $fontSize,
                $fontFile
            ) <= $maxWidth
        ) {
            $line = $test;
        } else {

            if ($line) {
                $lines[] = $line;
            }

            $line = $word;
        }
    }

    if ($line) {
        $lines[] = $line;
    }

    return $lines;
}

function fitText($text, $rect, $style, $fontFile) {

    $width = $rect['w'] - 120;

    for (
        $size = $style['maxFontSize'];
        $size >= $style['minFontSize'];
        $size--
    ) {

        $lines = wrapTextCustom(
            $text,
            $width,
            $size,
            $fontFile
        );

        $lineHeight = ceil(
            $size * $style['lineHeight']
        );

        $totalHeight =
            count($lines) * $lineHeight;

        $widest = 0;

        foreach ($lines as $line) {

            $widest = max(
                $widest,
                measureTextWidth(
                    $line,
                    $size,
                    $fontFile
                )
            );
        }

        if (
            $widest <= $width &&
            $totalHeight <= $rect['h']
        ) {
            return [
                'size' => $size,
                'lines' => $lines,
                'lineHeight' => $lineHeight,
                'totalHeight' => $totalHeight
            ];
        }
    }

    return [
        'size' => $style['minFontSize'],
        'lines' => [$text],
        'lineHeight' => 14,
        'totalHeight' => 14
    ];
}

try {

    $text = normalizeText($text);

    $imageBuffer = downloadBuffer(
        BRAT_IMAGE_URL
    );

    $fontBuffer = downloadBuffer(
        BRAT_FONT_URL
    );

    $fontFile =
        sys_get_temp_dir() .
        '/poppins_' .
        time() .
        '.ttf';

    file_put_contents(
        $fontFile,
        $fontBuffer
    );

    $image = imagecreatefromstring(
        $imageBuffer
    );

    if (!$image) {
        throw new Exception(
            'Gagal membuka template'
        );
    }

    $canvas = imagecreatetruecolor(
        $CANVAS['width'],
        $CANVAS['height']
    );

    imagecopyresampled(
        $canvas,
        $image,
        0,
        0,
        0,
        0,
        $CANVAS['width'],
        $CANVAS['height'],
        imagesx($image),
        imagesy($image)
    );

    $rect = getSafeRect(
        $SAFE_ZONE
    );

    $fit = fitText(
        $text,
        $rect,
        $TEXT_STYLE,
        $fontFile
    );

    $actualFontSize = max(
        16,
        $fit['size'] * 1.02
    );

    $actualLineHeight =
        $actualFontSize * 1.15;

    $totalHeight =
        count($fit['lines']) *
        $actualLineHeight;

    $startY =
        $rect['y'] +
        (($rect['h'] - $totalHeight) / 2);

    $color = imagecolorallocate(
        $canvas,
        17,
        17,
        17
    );

    foreach (
        $fit['lines']
        as $index => $line
    ) {

        $box = imagettfbbox(
            $actualFontSize,
            0,
            $fontFile,
            $line
        );

        $lineWidth = abs(
            $box[2] - $box[0]
        );

        $x =
            ($rect['centerX'] + 35) -
            ($lineWidth / 2);

        $y =
            $startY +
            ($index * $actualLineHeight) +
            $actualFontSize;

        imagettftext(
            $canvas,
            $actualFontSize,
            0,
            (int)$x,
            (int)$y,
            $color,
            $fontFile,
            $line
        );
    }

    header('Content-Type: image/png');

    imagepng($canvas);

    imagedestroy($canvas);
    imagedestroy($image);

    @unlink($fontFile);

} catch (Exception $e) {

    header('Content-Type: application/json');

    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>