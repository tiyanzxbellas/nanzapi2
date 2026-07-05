<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Naruto Quote Generator 
// Contoh: {"text":"Nah, I'd win ✌️"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Teks (support emoji)

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$text = $_GET['text'] ?? "Nah, I'd win ✌️";

// Batas frame
$frameX1 = 267;
$frameY1 = 673;
$frameX2 = 985;
$frameY2 = 1165;

// Setting font
$fontMaxSize = 85;
$fontMinSize = 16;
$fontPadding = 20;

$bgUrl = 'https://nanzzcode.my.id/Naruto.png';
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
        
        $lineHeight = $size * 1.4;
        $totalHeight = count($lines) * $lineHeight;
        
        if ($totalHeight > $maxHeight) break;
        
        $bestSize = $size;
        $bestLines = $lines;
    }
    
    return [
        'size' => $bestSize, 
        'lines' => $bestLines, 
        'lineHeight' => $bestSize * 1.4
    ];
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
    
    // WARNA BRAT PATRICK: Teks hitam, shadow putih
    $black = imagecolorallocate($canvas, 17, 17, 17);       // Hitam #111111
    $white = imagecolorallocate($canvas, 255, 255, 255);    // Putih
    
    $fontFile = sys_get_temp_dir() . '/poppins_' . time() . '.ttf';
    
    if (!$fontBuffer) throw new Exception('Gagal download font');
    file_put_contents($fontFile, $fontBuffer);
    
    $areaW = $frameX2 - $frameX1 - ($fontPadding * 2);
    $areaH = $frameY2 - $frameY1 - ($fontPadding * 2);
    
    $fit = autoFitText($text, $fontFile, $fontMaxSize, $fontMinSize, $areaW, $areaH);
    
    $totalTextH = count($fit['lines']) * $fit['lineHeight'];
    $startY = $frameY1 + ($areaH - $totalTextH) / 2 + $fit['size'];
    
    // Stroke tebal (outline putih)
    $stroke = max(2, round($fit['size'] * 0.08));
    
    foreach ($fit['lines'] as $i => $line) {
        $box = imagettfbbox($fit['size'], 0, $fontFile, $line);
        $textW = abs($box[2] - $box[0]);
        $x = $frameX1 + ($frameX2 - $frameX1 - $textW) / 2;
        $y = $startY + ($i * $fit['lineHeight']);
        
        // Stroke putih (outline)
        for ($sx = -$stroke; $sx <= $stroke; $sx++) {
            for ($sy = -$stroke; $sy <= $stroke; $sy++) {
                imagettftext($canvas, $fit['size'], 0, (int)$x + $sx, (int)$y + $sy, $white, $fontFile, $line);
            }
        }
        
        // Teks hitam di atas
        imagettftext($canvas, $fit['size'], 0, (int)$x, (int)$y, $black, $fontFile, $line);
    }
    
    @unlink($fontFile);
    
    imagejpeg($canvas);
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