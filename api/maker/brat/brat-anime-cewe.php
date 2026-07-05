<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Text Quote Generator - Auto-fit text dengan batas frame
// Contoh: {"text":"Halo dunia!"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$text = $_GET['text'] ?? 'Halo dunia!';

if (!$text) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Parameter text diperlukan'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Frame area
$frameX1 = 215;
$frameY1 = 553;
$frameX2 = 815;
$frameY2 = 954;

// Setting
$fontMaxSize = 60;
$fontMinSize = 16;
$fontPadding = 20;

$bgUrl = 'https://nanzzcode.my.id/brat-anime-cewe.jpg'; // GANTI DENGAN URL GAMBAR LO
$fontUrl = 'https://raw.githubusercontent.com/SaurusAraAra/mentahan/main/font/Poppins.ttf';

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
                $currentLine = $word;
            }
        }
        if ($currentLine) $lines[] = $currentLine;
        
        $lineHeight = $size * 1.4;
        $totalHeight = count($lines) * $lineHeight;
        
        if ($totalHeight > $maxHeight) break;
        
        $bestSize = $size;
        $bestLines = $lines;
    }
    
    return ['size' => $bestSize, 'lines' => $bestLines, 'lineHeight' => $bestSize * 1.4];
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
    
    $black = imagecolorallocate($canvas, 17, 17, 17);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    
    $fontFile = sys_get_temp_dir() . '/poppins_' . time() . '.ttf';
    if (!$fontBuffer) throw new Exception('Gagal download font');
    file_put_contents($fontFile, $fontBuffer);
    
    $areaW = $frameX2 - $frameX1 - ($fontPadding * 2);
    $areaH = $frameY2 - $frameY1 - ($fontPadding * 2);
    
    $fit = autoFitText($text, $fontFile, $fontMaxSize, $fontMinSize, $areaW, $areaH);
    
    $totalTextH = count($fit['lines']) * $fit['lineHeight'];
    $startY = $frameY1 + ($areaH - $totalTextH) / 2 + $fit['size'];
    
    $stroke = max(2, round($fit['size'] * 0.06));
    
    foreach ($fit['lines'] as $i => $line) {
        $box = imagettfbbox($fit['size'], 0, $fontFile, $line);
        $textW = abs($box[2] - $box[0]);
        $x = $frameX1 + ($frameX2 - $frameX1 - $textW) / 2;
        $y = $startY + ($i * $fit['lineHeight']);
        
        // Stroke putih
        for ($sx = -$stroke; $sx <= $stroke; $sx++) {
            for ($sy = -$stroke; $sy <= $stroke; $sy++) {
                imagettftext($canvas, $fit['size'], 0, (int)$x + $sx, (int)$y + $sy, $white, $fontFile, $line);
            }
        }
        
        // Teks hitam
        imagettftext($canvas, $fit['size'], 0, (int)$x, (int)$y, $black, $fontFile, $line);
    }
    
    @unlink($fontFile);
    
    imagejpeg($canvas, null, 95);
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