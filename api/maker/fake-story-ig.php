<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fake Instagram Story Maker
// Contoh: {"file": "foto.jpg", "name": "John Doe", "text": "Hello World"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param name Nama pengguna
// @param text Teks story

set_time_limit(60);

$name = $_POST['name'] ?? $_GET['name'] ?? 'User';
$text = $_POST['text'] ?? $_GET['text'] ?? '';

if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Parameter text wajib diisi'
    ]);
    exit;
}

// Upload PP ke GoBox
$ppUrl = '';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileMime = mime_content_type($fileTmp);
    
    $ch = curl_init('https://www.gobox.my.id/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($fileTmp, $fileMime, $fileName)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0',
        'Accept: application/json'
    ]);
    
    $uploadResponse = curl_exec($ch);
    curl_close($ch);
    
    $uploadData = json_decode($uploadResponse, true);
    $ppUrl = $uploadData['url'] ?? $uploadData['data']['url'] ?? '';
}

if (empty($ppUrl)) {
    $ppUrl = 'https://raw.githubusercontent.com/uploader762/dat4/main/uploads/e0f993-1777126212302.jpg';
}

// Download assets
if (!file_exists('./font')) mkdir('./font', 0755, true);

$ch = curl_init('https://raw.githubusercontent.com/uploader762/dat2/main/uploads/957068-1777109622178.ttf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
file_put_contents('./font/Nuninto-SemiBold.ttf', curl_exec($ch));
curl_close($ch);

$ch = curl_init('https://raw.githubusercontent.com/uploader762/dat1/main/uploads/5206ca-1777112931358.otf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
file_put_contents('./font/Cooper-black.ttf', curl_exec($ch));
curl_close($ch);

$ch = curl_init('https://raw.githubusercontent.com/uploader762/dat4/main/uploads/036484-1777108103055.jpg');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$bgData = curl_exec($ch);
curl_close($ch);

$bg = imagecreatefromstring($bgData);
$bgW = imagesx($bg);
$bgH = imagesy($bg);

// Download PP
$ch = curl_init($ppUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$ppData = curl_exec($ch);
curl_close($ch);

$avatar = @imagecreatefromstring($ppData);
if (!$avatar) {
    $avatar = imagecreatetruecolor(100, 100);
    imagefill($avatar, 0, 0, imagecolorallocate($avatar, 200, 200, 200));
}

$avatarW = imagesx($avatar);
$avatarH = imagesy($avatar);

// Position
$pL = ['x' => 72, 'y' => 299];
$pR = ['x' => 191, 'y' => 293];
$pT = ['x' => 126, 'y' => 239];
$pB = ['x' => 144, 'y' => 359];

$cx = ($pL['x'] + $pR['x']) / 2 + 2;
$cy = ($pT['y'] + $pB['y']) / 2;
$r = ($pR['x'] - $pL['x']) / 2;

$canvas = imagecreatetruecolor($bgW, $bgH);
imagecopy($canvas, $bg, 0, 0, 0, 0, $bgW, $bgH);

// Circle crop avatar
$s = min($avatarW, $avatarH);
$sx = ($avatarW - $s) / 2;
$sy = ($avatarH - $s) / 2;

$avatarResized = imagecreatetruecolor($r * 2, $r * 2);
imagecopyresampled($avatarResized, $avatar, 0, 0, $sx, $sy, $r * 2, $r * 2, $s, $s);

for ($x = 0; $x < $r * 2; $x++) {
    for ($y = 0; $y < $r * 2; $y++) {
        $dx = $x - $r;
        $dy = $y - $r;
        if (($dx * $dx + $dy * $dy) <= ($r * $r)) {
            $color = imagecolorat($avatarResized, $x, $y);
            imagesetpixel($canvas, $cx - $r + $x, $cy - $r + $y, $color);
        }
    }
}

// Name text
$white = imagecolorallocate($canvas, 255, 255, 255);
imagettftext($canvas, 48, 0, $cx + $r + 19, $cy, $white, './font/Nuninto-SemiBold.ttf', $name);

// Story text
$left = 120;
$right = 1196;
$topY = 428;
$bottomY = 1549;
$boxX = ($left + $right) / 2;
$boxY = ($topY + $bottomY) / 2;
$maxW = $right - $left;
$maxH = $bottomY - $topY;
$fontSize = 74;

do {
    $lines = explode("\n", wordwrap($text, intval($maxW / ($fontSize * 0.6))));
    $lineHeight = $fontSize * 1.1;
    $totalHeight = count($lines) * $lineHeight;
    $fontSize -= 2;
} while (($totalHeight > $maxH) && $fontSize > 20);

$startY = $boxY - (count($lines) * $lineHeight / 2) + ($lineHeight / 2);

foreach ($lines as $i => $line) {
    $box = imagettfbbox($fontSize, 0, './font/Cooper-black.ttf', $line);
    $lineW = $box[2] - $box[0];
    imagettftext($canvas, $fontSize, 0, $boxX - ($lineW / 2), $startY + ($i * $lineHeight), $white, './font/Cooper-black.ttf', $line);
}

// Output langsung gambar
header('Content-Type: image/png');
header('X-Creator: Nanzz');
imagepng($canvas);
imagedestroy($canvas);
imagedestroy($bg);
imagedestroy($avatar);
?>