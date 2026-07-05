<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fake iNews Breaking News 
// Contoh: {"text":"Viral! Jokowi mencuri 19jt lapangan pekerjaan dari anaknya","url":"https://www.upload.ee/image/19400325/images.webp"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Judul berita
// @param url URL gambar

header('Content-Type: image/png');

$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$text = trim($_GET['text'] ?? 'Jokowi mencuri 19jt lapangan pekerjaan dari anaknya');
$imageUrl = trim($_GET['url'] ?? 'https://www.upload.ee/image/19400325/images.webp');

$BACKGROUND_URL = 'https://nanzzcode.my.id/cdn/berita.png';
$PRESENTER_URL  = 'https://nanzzcode.my.id/cdn/presenter.png';
$FONT_URL       = 'https://nanzzcode.my.id/cdn/Arial-bold.ttf';

$TV_X = 353; $TV_Y = 59; $TV_W = 1149; $TV_H = 580;

// Frame Judul: 261,749,1689,863
$TITLE_X1 = 261; $TITLE_Y1 = 749;
$TITLE_X2 = 1689; $TITLE_Y2 = 863;
$TITLE_W = $TITLE_X2 - $TITLE_X1;
$TITLE_H = $TITLE_Y2 - $TITLE_Y1;
$TITLE_CENTER_Y = ($TITLE_Y1 + $TITLE_Y2) / 2;
$PADDING_LEFT = 15;
$MAX_FONT = $TITLE_H;
$MIN_FONT = 14;

function downloadBuffer($url) { $ch = curl_init(); curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 30]); $res = curl_exec($ch); curl_close($ch); return $res; }
function imageFromUrl($url) { $buf = downloadBuffer($url); return $buf ? imagecreatefromstring($buf) : false; }

function wrapText($text, $fontSize, $fontFile, $maxWidth) {
    $words = explode(' ', $text);
    $lines = []; $current = '';
    foreach ($words as $word) {
        $test = $current ? "$current $word" : $word;
        $box = imagettfbbox($fontSize, 0, $fontFile, $test);
        if (abs($box[2] - $box[0]) > $maxWidth && $current) {
            $lines[] = $current; $current = $word;
        } else { $current = $test; }
    }
    if ($current) $lines[] = $current;
    return $lines;
}

try {
    $bg = imageFromUrl($BACKGROUND_URL);
    if (!$bg) throw new Exception('Background gagal');
    $w = imagesx($bg); $h = imagesy($bg);
    $canvas = imagecreatetruecolor($w, $h);
    imagecopy($canvas, $bg, 0, 0, 0, 0, $w, $h);
    imagedestroy($bg);

    $photo = imageFromUrl($imageUrl);
    if ($photo) {
        $srcW = imagesx($photo); $srcH = imagesy($photo);
        $scale = max($TV_W / $srcW, $TV_H / $srcH);
        $nW = ceil($srcW * $scale); $nH = ceil($srcH * $scale);
        $tmp = imagecreatetruecolor($TV_W, $TV_H);
        imagecopyresampled($tmp, $photo, -(($nW-$TV_W)/2), -(($nH-$TV_H)/2), 0, 0, $nW, $nH, $srcW, $srcH);
        imagecopy($canvas, $tmp, $TV_X, $TV_Y, 0, 0, $TV_W, $TV_H);
        imagedestroy($tmp); imagedestroy($photo);
    }

    $pr = imageFromUrl($PRESENTER_URL);
    if ($pr) { imagecopy($canvas, $pr, 0, 0, 0, 0, $w, $h); imagedestroy($pr); }

    $fontFile = sys_get_temp_dir() . '/arial_' . uniqid() . '.ttf';
    file_put_contents($fontFile, downloadBuffer($FONT_URL));
    $headline = strtoupper($text);
    
    $maxW = $TITLE_W - $PADDING_LEFT;
    $fontSize = $MAX_FONT;
    $lines = [];
    
    // Auto shrink sampai muat dalam frame
    while ($fontSize >= $MIN_FONT) {
        $lines = wrapText($headline, $fontSize, $fontFile, $maxW);
        $lh = $fontSize * 1.15;
        if (count($lines) * $lh <= $TITLE_H) break;
        $fontSize -= 2;
    }
    
    $lh = $fontSize * 1.15;
    $totalH = count($lines) * $lh;
    $startY = $TITLE_CENTER_Y - ($totalH / 2) + $fontSize;
    
    $blue = imagecolorallocate($canvas, 10, 42, 155);
    foreach ($lines as $i => $line) {
        imagettftext($canvas, $fontSize, 0, $TITLE_X1 + $PADDING_LEFT, (int)($startY + $i * $lh), $blue, $fontFile, $line);
    }
    
    @unlink($fontFile);
    imagepng($canvas); imagedestroy($canvas);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Nanzz', 'status' => false, 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>