<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Smeme Generator 
// Contoh: {"atas":"iya","bawah":"bener juga","url":"https://www.upload.ee/image/19414990/images__1_.jpeg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param atas Teks Atas
// @param bawah Teks Bawah
// @param url URL Gambar

header('Content-Type: image/png');

set_time_limit(60);

$top = strtoupper(trim($_GET['atas'] ?? 'FOR'));
$bottom = strtoupper(trim($_GET['bawah'] ?? 'YOU'));
$imageUrl = $_GET['url'] ?? 'https://raw.githubusercontent.com/SaurusAraAra/mentahan/refs/heads/main/kucing_megang_bunga.jpg';

define(
    'FONT_URL',
    'https://raw.githubusercontent.com/SaurusAraAra/mentahan/main/font/Impact.ttf'
);

function downloadBuffer($url)
{
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

function measureTextWidth($text, $fontSize, $fontFile)
{
    $box = imagettfbbox(
        $fontSize,
        0,
        $fontFile,
        $text
    );

    return abs($box[2] - $box[0]);
}

function wrapText(
    $text,
    $maxWidth,
    $fontSize,
    $fontFile
) {
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

function fitText(
    $text,
    $maxWidth,
    $startSize,
    $fontFile
) {
    $size = $startSize;

    while ($size > 12) {

        $lines = wrapText(
            $text,
            $maxWidth,
            $size,
            $fontFile
        );

        $widest = 0;

        foreach ($lines as $line) {

            $w = measureTextWidth(
                $line,
                $size,
                $fontFile
            );

            if ($w > $widest) {
                $widest = $w;
            }
        }

        if ($widest <= $maxWidth) {
            break;
        }

        $size -= 2;
    }

    return [
        'size' => $size,
        'lines' => wrapText(
            $text,
            $maxWidth,
            $size,
            $fontFile
        ),
        'lineHeight' => $size * 1.15
    ];
}

function drawTextWithStroke(
    $image,
    $fontFile,
    $fontSize,
    $x,
    $y,
    $text,
    $strokeColor,
    $fillColor
) {
    $stroke = max(
        2,
        round($fontSize * 0.08)
    );

    for (
        $sx = -$stroke;
        $sx <= $stroke;
        $sx++
    ) {
        for (
            $sy = -$stroke;
            $sy <= $stroke;
            $sy++
        ) {

            imagettftext(
                $image,
                $fontSize,
                0,
                $x + $sx,
                $y + $sy,
                $strokeColor,
                $fontFile,
                $text
            );
        }
    }

    imagettftext(
        $image,
        $fontSize,
        0,
        $x,
        $y,
        $fillColor,
        $fontFile,
        $text
    );
}

try {

    $imageBuffer = downloadBuffer(
        $imageUrl
    );

    $fontBuffer = downloadBuffer(
        FONT_URL
    );

    $fontFile =
        sys_get_temp_dir() .
        '/impact_' .
        uniqid() .
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
            'Gagal membuka gambar'
        );
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $canvas = imagecreatetruecolor(
        $width,
        $height
    );

    imagecopyresampled(
        $canvas,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $width,
        $height
    );

    $padding = round(
        min($width, $height) * 0.04
    );

    $maxWidth =
        $width - ($padding * 2);

    $fontRatio = round(
        min($width, $height) * 0.10
    );

    $strokeColor =
        imagecolorallocate(
            $canvas,
            0,
            0,
            0
        );

    $fillColor =
        imagecolorallocate(
            $canvas,
            255,
            255,
            255
        );

    if (!empty($top)) {

        $topFit = fitText(
            $top,
            $maxWidth,
            $fontRatio,
            $fontFile
        );

        $startY =
            $padding +
            $topFit['size'];

        foreach (
            $topFit['lines']
            as $i => $line
        ) {

            $box = imagettfbbox(
                $topFit['size'],
                0,
                $fontFile,
                $line
            );

            $textWidth = abs(
                $box[2] - $box[0]
            );

            $x = (
                $width -
                $textWidth
            ) / 2;

            $y =
                $startY +
                ($i * $topFit['lineHeight']);

            drawTextWithStroke(
                $canvas,
                $fontFile,
                $topFit['size'],
                (int)$x,
                (int)$y,
                $line,
                $strokeColor,
                $fillColor
            );
        }
    }

    if (!empty($bottom)) {

        $bottomFit = fitText(
            $bottom,
            $maxWidth,
            $fontRatio,
            $fontFile
        );

        $lineCount =
            count(
                $bottomFit['lines']
            );

        for (
            $i = 0;
            $i < $lineCount;
            $i++
        ) {

            $line =
                $bottomFit['lines'][$i];

            $box = imagettfbbox(
                $bottomFit['size'],
                0,
                $fontFile,
                $line
            );

            $textWidth = abs(
                $box[2] - $box[0]
            );

            $x = (
                $width -
                $textWidth
            ) / 2;

            $y =
                $height -
                $padding -
                (
                    ($lineCount - 1 - $i)
                    *
                    $bottomFit['lineHeight']
                );

            drawTextWithStroke(
                $canvas,
                $fontFile,
                $bottomFit['size'],
                (int)$x,
                (int)$y,
                $line,
                $strokeColor,
                $fillColor
            );
        }
    }

    imagepng($canvas);

    imagedestroy($canvas);
    imagedestroy($image);

    @unlink($fontFile);

} catch (Exception $e) {

    header(
        'Content-Type: application/json'
    );

    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>