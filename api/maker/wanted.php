<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Fake Wanted Poster Generator
// Contoh: {"nama":"Jokowi Dodo","harga":"150.000.000","url":"https://www.upload.ee/image/19400325/images.webp"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Nama Buronan
// @param harga Jumlah Bounty
// @param url URL Foto Buronan

header('Content-Type: image/png');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$nama = strtoupper(trim($_GET['nama'] ?? 'Jokowi Dodo'));
$harga = trim($_GET['harga'] ?? '150.000.000');
$imageUrl = trim($_GET['url'] ?? 'https://www.upload.ee/image/19400325/images.webp');

// ==================== SHORTCUT EDIT POSISI ====================
// Foto: X1=74 Y1=253 X2=709 Y2=728
$imgX1 = 74; $imgY1 = 253; $imgX2 = 709; $imgY2 = 728;

// Nama: X1=66 Y1=833 X2=696 Y2=947 | FontSize=70
$namaX1 = 66; $namaY1 = 833; $namaX2 = 696; $namaY2 = 947;
$namaFontSize = 70;
$namaOffsetY = -6; // minus = naik, plus = turun

// Block Dollar + Harga (center bersama): X1=66 Y1=964 X2=712 Y2=1041
$blockX1 = 66; $blockY1 = 964; $blockX2 = 712; $blockY2 = 1041;
$blockW = $blockX2 - $blockX1;
$blockH = $blockY2 - $blockY1;
$blockCenterY = ($blockY1 + $blockY2) / 2;

// Dollar Icon: W=51 H=77
$dollarW = 51; $dollarH = 77;

// Harga: MaxFont=70
$hargaMaxFont = 70;
$iconGap = 0; // jarak dollar ke teks
$centerOffsetX = -30; // minus = geser kiri, plus = geser kanan
// =============================================================

// ==================== URL ASSETS ====================
$bgUrl = 'https://nanzzcode.my.id/cdn/wanted1.png';
$dollarUrl = 'https://nanzzcode.my.id/cdn/Dollar.png';
$fontNamaUrl = 'https://nanzzcode.my.id/cdn/PlayfairDisplay-Bold.ttf';
$fontHargaUrl = 'https://api-nanas.my.id/Ryu-Japanese.ttf';
// ===================================================

function downloadBuffer($url) { 
    $ch = curl_init(); 
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_FOLLOWLOCATION => true, 
        CURLOPT_SSL_VERIFYPEER => false, 
        CURLOPT_TIMEOUT => 30
    ]); 
    $res = curl_exec($ch); 
    curl_close($ch); 
    return $res; 
}

try {
    $bg = imagecreatefromstring(downloadBuffer($bgUrl));
    if (!$bg) throw new Exception('Gagal load background');
    
    $w = imagesx($bg); $h = imagesy($bg);
    $canvas = imagecreatetruecolor($w, $h);
    imagecopy($canvas, $bg, 0, 0, 0, 0, $w, $h);
    imagedestroy($bg);

    // Foto
    $imgW = $imgX2 - $imgX1; $imgH = $imgY2 - $imgY1;
    $photo = @imagecreatefromstring(downloadBuffer($imageUrl));
    if ($photo) {
        $srcW = imagesx($photo); $srcH = imagesy($photo);
        $scale = max($imgW / $srcW, $imgH / $srcH);
        $nW = ceil($srcW * $scale); $nH = ceil($srcH * $scale);
        $tmp = imagecreatetruecolor($imgW, $imgH);
        imagecopyresampled($tmp, $photo, -(($nW-$imgW)/2), -(($nH-$imgH)/2), 0, 0, $nW, $nH, $srcW, $srcH);
        imagecopy($canvas, $tmp, $imgX1, $imgY1, 0, 0, $imgW, $imgH);
        imagedestroy($tmp); imagedestroy($photo);
    }

    $fontNamaFile = sys_get_temp_dir() . '/wname_' . uniqid() . '.ttf';
    file_put_contents($fontNamaFile, downloadBuffer($fontNamaUrl));
    $fontHargaFile = sys_get_temp_dir() . '/wprice_' . uniqid() . '.ttf';
    file_put_contents($fontHargaFile, downloadBuffer($fontHargaUrl));

    $brown = imagecolorallocate($canvas, 60, 30, 10);

    // ========== NAMA ==========
    $namaW = $namaX2 - $namaX1;
    $namaH = $namaY2 - $namaY1;
    $namaCenterY = ($namaY1 + $namaY2) / 2;
    $nSize = $namaFontSize;
    while ($nSize > 16) { 
        $box = imagettfbbox($nSize, 0, $fontNamaFile, $nama); 
        $tw = abs($box[2]-$box[0]); $th = abs($box[7]-$box[1]); 
        if ($tw <= $namaW && $th <= $namaH) break; 
        $nSize -= 2; 
    }
    $box = imagettfbbox($nSize, 0, $fontNamaFile, $nama); 
    $tw = abs($box[2]-$box[0]); $th = abs($box[7]-$box[1]);
    imagettftext($canvas, $nSize, 0, (int)($namaX1+($namaW-$tw)/2), (int)($namaCenterY+$th/2+$namaOffsetY), $brown, $fontNamaFile, $nama);

    // ========== DOLLAR + HARGA CENTER ==========
    $hSize = $hargaMaxFont;
    while ($hSize > 14) {
        $box = imagettfbbox($hSize, 0, $fontHargaFile, $harga);
        $tw = abs($box[2]-$box[0]); $th = abs($box[7]-$box[1]);
        $totalW = $dollarW + $iconGap + $tw;
        if ($totalW <= $blockW - 10) break;
        $hSize -= 2;
    }
    
    $totalContentW = $dollarW + $iconGap + $tw;
    $startX = $blockX1 + ($blockW - $totalContentW) / 2 + $centerOffsetX;
    
    // Dollar Icon
    $dollarX = (int)$startX;
    $dollarY = (int)($blockCenterY - $dollarH / 2);
    
    $dollarImg = @imagecreatefromstring(downloadBuffer($dollarUrl));
    if ($dollarImg) {
        $dr = imagecreatetruecolor($dollarW, $dollarH);
        imagealphablending($dr, false); imagesavealpha($dr, true);
        imagefill($dr, 0, 0, imagecolorallocatealpha($dr, 0, 0, 0, 127));
        imagecopyresampled($dr, $dollarImg, 0, 0, 0, 0, $dollarW, $dollarH, imagesx($dollarImg), imagesy($dollarImg));
        imagecopy($canvas, $dr, $dollarX, $dollarY, 0, 0, $dollarW, $dollarH);
        imagedestroy($dollarImg); imagedestroy($dr);
    }
    
    // Harga
    $hx = (int)($startX + $dollarW + $iconGap);
    $hy = (int)($blockCenterY + ($th / 2) - 4);
    imagettftext($canvas, $hSize, 0, $hx, $hy, $brown, $fontHargaFile, $harga);

    @unlink($fontNamaFile); @unlink($fontHargaFile);
    imagepng($canvas); imagedestroy($canvas);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>