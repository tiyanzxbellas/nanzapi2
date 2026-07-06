<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nokia Chat Generator (Auto Fit + Alignment)
// Contoh: {"sender":"Tiyanz","pesan":"Halo bro! apa kabar?"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param sender Nama Pengirim
// @param pesan Isi Pesan

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$sender = $_GET['sender'] ?? 'Tiyanz';
$pesan = $_GET['pesan'] ?? 'Halo bro!';

// ==================== SHORTCUT ====================
// UKURAN FONT
$fontMaxPesan = 76;
$fontMaxSender = 100;
$fontMaxSenderBawah = 36;
$fontMinPesan = 20;
$fontMinSender = 53;
$fontMinSenderBawah = 36;

// ALIGNMENT: left | center | right
$alignPesan = 'center';
$alignSender = 'left';
$alignSenderBawah = 'left';

// POSISI OFFSET
$pesanOffsetX = 0;
$pesanOffsetY = 0;
$senderOffsetX = -34;
$senderOffsetY = 22;
$senderBawahOffsetX = 0;
$senderBawahOffsetY = 14;
// =================================================

$bgUrl = 'https://tiyanzcode.my.id/nokia.jpg';
$fontUrl = 'https://tiyanzcode.my.id/PixelifySans.ttf';

function downloadBuffer($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function autoFitText($text, $fontFile, $maxSize, $minSize, $maxWidth, $maxHeight) {
    $bestSize = $minSize;
    $bestLines = [$text];
    
    for ($size = $minSize; $size <= $maxSize; $size += 2) {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            $box = imagettfbbox($size, 0, $fontFile, $testLine);
            $testWidth = abs($box[2] - $box[0]);
            
            if ($testWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if ($currentLine) $lines[] = $currentLine;
                $box2 = imagettfbbox($size, 0, $fontFile, $word);
                if (abs($box2[2] - $box2[0]) > $maxWidth) {
                    $chars = mb_str_split($word);
                    $partial = '';
                    foreach ($chars as $char) {
                        $testPartial = $partial . $char;
                        $box3 = imagettfbbox($size, 0, $fontFile, $testPartial);
                        if (abs($box3[2] - $box3[0]) <= $maxWidth) {
                            $partial = $testPartial;
                        } else {
                            if ($partial) $lines[] = $partial;
                            $partial = $char;
                        }
                    }
                    if ($partial) $currentLine = $partial;
                } else {
                    $currentLine = $word;
                }
            }
        }
        if ($currentLine) $lines[] = $currentLine;
        
        $lineHeight = $size * 1.5;
        $totalHeight = count($lines) * $lineHeight;
        
        if ($totalHeight > $maxHeight) break;
        
        $bestSize = $size;
        $bestLines = $lines;
    }
    
    return ['size' => $bestSize, 'lines' => $bestLines, 'lineHeight' => $bestSize * 1.5];
}

function getX($alignment, $frameLeft, $frameRight, $textWidth, $padding, $offset) {
    switch ($alignment) {
        case 'left':
            return $frameLeft + $padding + $offset;
        case 'right':
            return $frameRight - $padding - $textWidth + $offset;
        case 'center':
        default:
            return $frameLeft + ($frameRight - $frameLeft - $textWidth) / 2 + $offset;
    }
}

try {
    $bgBuffer = downloadBuffer($bgUrl);
    $fontBuffer = downloadBuffer($fontUrl);
    
    $bg = imagecreatefromstring($bgBuffer);
    if (!$bg) throw new Exception('Gagal load background');
    
    $width = imagesx($bg);
    $height = imagesy($bg);
    
    $canvas = imagecreatetruecolor($width, $height);
    imagecopy($canvas, $bg, 0, 0, 0, 0, $width, $height);
    imagedestroy($bg);
    
    $black = imagecolorallocate($canvas, 0, 0, 0);
    
    $fontFile = sys_get_temp_dir() . '/pixelify_' . time() . '.ttf';
    if (!$fontBuffer) throw new Exception('Gagal download font');
    file_put_contents($fontFile, $fontBuffer);
    
    // ===== PESAN (249,403,995,768) =====
    $boxW1 = 995 - 249 - 20;
    $boxH1 = 768 - 403 - 20;
    $fit1 = autoFitText($pesan, $fontFile, $fontMaxPesan, $fontMinPesan, $boxW1-20, $boxH1-20);
    $startY1 = 403 + ($boxH1 - count($fit1['lines']) * $fit1['lineHeight']) / 2 + $fit1['size'] + $pesanOffsetY;
    
    foreach ($fit1['lines'] as $i => $line) {
        $box = imagettfbbox($fit1['size'], 0, $fontFile, $line);
        $tw = abs($box[2] - $box[0]);
        $x = getX($alignPesan, 249, 995, $tw, 10, $pesanOffsetX);
        $y = $startY1 + ($i * $fit1['lineHeight']);
        imagettftext($canvas, $fit1['size'], 0, (int)$x, (int)$y, $black, $fontFile, $line);
    }
    
    // ===== SENDER (372,308,644,380) =====
    $boxW2 = 644 - 372 - 10;
    $boxH2 = 380 - 308 - 10;
    $fit2 = autoFitText($sender, $fontFile, $fontMaxSender, $fontMinSender, $boxW2-10, $boxH2-10);
    $startY2 = 308 + ($boxH2 - count($fit2['lines']) * $fit2['lineHeight']) / 2 + $fit2['size'] + $senderOffsetY;
    
    foreach ($fit2['lines'] as $i => $line) {
        $box = imagettfbbox($fit2['size'], 0, $fontFile, $line);
        $tw = abs($box[2] - $box[0]);
        $x = getX($alignSender, 372, 644, $tw, 5, $senderOffsetX);
        $y = $startY2 + ($i * $fit2['lineHeight']);
        imagettftext($canvas, $fit2['size'], 0, (int)$x, (int)$y, $black, $fontFile, $line);
    }
    
    // ===== SENDER BAWAH (878,800,1006,842) =====
    $boxW3 = 1006 - 878 - 6;
    $boxH3 = 842 - 800 - 6;
    $fit3 = autoFitText($sender, $fontFile, $fontMaxSenderBawah, $fontMinSenderBawah, $boxW3-6, $boxH3-6);
    $startY3 = 800 + ($boxH3 - count($fit3['lines']) * $fit3['lineHeight']) / 2 + $fit3['size'] + $senderBawahOffsetY;
    
    foreach ($fit3['lines'] as $i => $line) {
        $box = imagettfbbox($fit3['size'], 0, $fontFile, $line);
        $tw = abs($box[2] - $box[0]);
        $x = getX($alignSenderBawah, 878, 1006, $tw, 3, $senderBawahOffsetX);
        $y = $startY3 + ($i * $fit3['lineHeight']);
        imagettftext($canvas, $fit3['size'], 0, (int)$x, (int)$y, $black, $fontFile, $line);
    }
    
    @unlink($fontFile);
    imagejpeg($canvas);
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