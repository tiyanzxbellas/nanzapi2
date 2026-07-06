<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fake WhatsApp Story Generator
// Contoh: {"nama":"Tiyanz","pesan":"Halo semua!","profile":"https://example.com/foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Nama di Story
// @param pesan Pesan Story
// @param profile URL Foto Profil

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$nama = $_GET['nama'] ?? 'Tiyanz';
$pesan = $_GET['pesan'] ?? 'Halo semua!';
$profileUrl = $_GET['profile'] ?? 'https://i.pinimg.com/originals/6a/74/83/6a74838448f8b1238c69c8e3787f4e1b.jpg';

// ==================== SHORTCUT TATA LETAK ====================
// PROFILE CIRCLE
$profileCx = 471; $profileCy = 438; $profileRadius = 237; $profileScale = 1.0;

// NAMA
$namaFontMax = 65; $namaFontMin = 18; $namaAlign = 'left';
$namaOffsetX = -25; $namaOffsetY = 90;
$namaColorR = 255; $namaColorG = 255; $namaColorB = 255;

// PESAN
$pesanFontMax = 65; $pesanFontMin = 16; $pesanAlign = 'center';
$pesanOffsetX = 0; $pesanOffsetY = -100;
$pesanColorR = 180; $pesanColorG = 180; $pesanColorB = 180;

// FRAME AREA
$namaFrameX1 = 209; $namaFrameY1 = 1115; $namaFrameX2 = 840; $namaFrameY2 = 1268;
$pesanFrameX1 = 185; $pesanFrameY1 = 687; $pesanFrameX2 = 881; $pesanFrameY2 = 1173;
// =============================================================

$bgUrl = 'https://tiyanzcode.my.id/Status_wa.jpg';
$fontUrl = 'https://tiyanzcode.my.id/Roboto.ttf';

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

function getAlignX($align, $frameX1, $frameX2, $textWidth, $offsetX) {
    $frameW = $frameX2 - $frameX1;
    switch ($align) {
        case 'left': return $frameX1 + 10 + $offsetX;
        case 'right': return $frameX2 - 10 - $textWidth + $offsetX;
        default: return $frameX1 + ($frameW - $textWidth) / 2 + $offsetX;
    }
}

function wrapText($text, $fontSize, $fontFile, $maxWidth) {
    $words = explode(' ', $text);
    $lines = [];
    $currentLine = '';
    
    foreach ($words as $word) {
        $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
        $box = imagettfbbox($fontSize, 0, $fontFile, $testLine);
        $testWidth = abs($box[2] - $box[0]);
        
        if ($testWidth <= $maxWidth) {
            $currentLine = $testLine;
        } else {
            if ($currentLine) $lines[] = $currentLine;
            
            // Cek kalo 1 kata kepanjangan, potong
            $box2 = imagettfbbox($fontSize, 0, $fontFile, $word);
            if (abs($box2[2] - $box2[0]) > $maxWidth) {
                $chars = mb_str_split($word);
                $partial = '';
                foreach ($chars as $char) {
                    $testPartial = $partial . $char;
                    $box3 = imagettfbbox($fontSize, 0, $fontFile, $testPartial);
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
    
    return $lines;
}

try {
    $bgBuffer = downloadBuffer($bgUrl);
    $profileBuffer = downloadBuffer($profileUrl);
    $fontBuffer = downloadBuffer($fontUrl);
    
    $template = imagecreatefromstring($bgBuffer);
    if (!$template) throw new Exception('Gagal load template');
    
    $profile = imagecreatefromstring($profileBuffer);
    if (!$profile) throw new Exception('Gagal load foto profil');
    
    $width = imagesx($template);
    $height = imagesy($template);
    $canvas = imagecreatetruecolor($width, $height);
    
    // Profile circle
    $cx = $profileCx; $cy = $profileCy; $r = $profileRadius; $diameter = $r * 2;
    
    $profileResized = imagecreatetruecolor($width, $height);
    $black = imagecolorallocate($profileResized, 0, 0, 0);
    imagefill($profileResized, 0, 0, $black);
    
    $profileW = imagesx($profile); $profileH = imagesy($profile);
    $scale = max($diameter / $profileW, $diameter / $profileH) / $profileScale;
    $srcW = $diameter / $scale; $srcH = $diameter / $scale;
    $srcX = ($profileW - $srcW) / 2; $srcY = ($profileH - $srcH) / 2;
    
    imagecopyresampled($profileResized, $profile, $cx - $r, $cy - $r, (int)$srcX, (int)$srcY, $diameter, $diameter, (int)$srcW, (int)$srcH);
    
    // Hapus hitam
    for ($x = max(0, $cx - $r - 5); $x < min($width, $cx + $r + 5); $x++) {
        for ($y = max(0, $cy - $r - 5); $y < min($height, $cy + $r + 5); $y++) {
            $templateColor = imagecolorat($template, $x, $y);
            $tr = ($templateColor >> 16) & 0xFF; $tg = ($templateColor >> 8) & 0xFF; $tb = $templateColor & 0xFF;
            $dist = sqrt(pow($x - $cx, 2) + pow($y - $cy, 2));
            if ($dist <= $r + 3 && $tr < 30 && $tg < 30 && $tb < 30) {
                imagesetpixel($template, $x, $y, imagecolorat($profileResized, $x, $y));
            }
        }
    }
    
    imagecopy($canvas, $template, 0, 0, 0, 0, $width, $height);
    
    $fontFile = sys_get_temp_dir() . '/roboto_' . time() . '.ttf';
    file_put_contents($fontFile, $fontBuffer);
    
    $namaColor = imagecolorallocate($canvas, $namaColorR, $namaColorG, $namaColorB);
    $pesanColor = imagecolorallocate($canvas, $pesanColorR, $pesanColorG, $pesanColorB);
    
    // ===== PESAN (dengan word wrap) =====
    $pesanW = $pesanFrameX2 - $pesanFrameX1;
    $pesanH = $pesanFrameY2 - $pesanFrameY1;
    
    $fontSizePesan = $pesanFontMax;
    $linesPesan = [];
    
    for ($s = $pesanFontMax; $s >= $pesanFontMin; $s -= 2) {
        $linesPesan = wrapText($pesan, $s, $fontFile, $pesanW - 20);
        $totalH = count($linesPesan) * ($s * 1.4);
        if ($totalH <= $pesanH - 10) {
            $fontSizePesan = $s;
            break;
        }
    }
    
    $lineHeightPesan = $fontSizePesan * 1.4;
    $totalTextH = count($linesPesan) * $lineHeightPesan;
    $startY = $pesanFrameY1 + ($pesanH - $totalTextH) / 2 + $pesanOffsetY;
    
    foreach ($linesPesan as $i => $line) {
        $box = imagettfbbox($fontSizePesan, 0, $fontFile, $line);
        $tw = abs($box[2] - $box[0]);
        $x = getAlignX($pesanAlign, $pesanFrameX1, $pesanFrameX2, $tw, $pesanOffsetX);
        $y = $startY + ($i + 1) * $lineHeightPesan;
        imagettftext($canvas, $fontSizePesan, 0, (int)$x, (int)$y, $pesanColor, $fontFile, $line);
    }
    
    // ===== NAMA =====
    $namaW = $namaFrameX2 - $namaFrameX1; $namaH = $namaFrameY2 - $namaFrameY1;
    
    for ($s = $namaFontMax; $s >= $namaFontMin; $s -= 2) {
        $box = imagettfbbox($s, 0, $fontFile, $nama);
        $tw = abs($box[2] - $box[0]); $th = abs($box[7] - $box[1]);
        if ($tw <= $namaW - 20) {
            $textWNama = $tw; $textHNama = $th; $fontSizeNama = $s; break;
        }
    }
    
    $namaTextX = getAlignX($namaAlign, $namaFrameX1, $namaFrameX2, $textWNama, $namaOffsetX);
    $namaTextY = $namaFrameY1 + ($namaH - $textHNama) / 2 + $fontSizeNama + $namaOffsetY;
    imagettftext($canvas, $fontSizeNama, 0, (int)$namaTextX, (int)$namaTextY, $namaColor, $fontFile, $nama);
    
    @unlink($fontFile);
    imagedestroy($template); imagedestroy($profile); imagedestroy($profileResized);
    imagejpeg($canvas, null, 95);
    imagedestroy($canvas);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>