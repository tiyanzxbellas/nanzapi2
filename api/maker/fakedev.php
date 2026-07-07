<?php
error_reporting(0);
ini_set('display_errors', '0');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

// Fungsi encode URL
function encode($v) {
    $out = '';
    $len = strlen($v);
    for ($i = 0; $i < $len; $i++) {
        $c = $v[$i];
        if (ctype_alnum($c) || $c == '-' || $c == '_' || $c == '.' || $c == '~') {
            $out .= $c;
        } else {
            $out .= '%' . strtoupper(dechex(ord($c)));
        }
    }
    return $out;
}

function usage() {
    echo "pakai: php fakedev.php -profile \"url\" -name \"nama\" -bio \"bio\"\n";
}

// ========== CLI MODE ==========
if (php_sapi_name() === 'cli') {
    $argv = $_SERVER['argv'];
    $argc = $_SERVER['argc'];
    
    $profile = '';
    $name = '';
    $bio = '';
    
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg == '-profile' && $i + 1 < $argc) {
            $profile = $argv[++$i];
        } elseif ($arg == '-name' && $i + 1 < $argc) {
            $name = $argv[++$i];
        } elseif ($arg == '-bio' && $i + 1 < $argc) {
            $bio = $argv[++$i];
        }
    }
    
    if (empty($profile) || empty($name) || empty($bio)) {
        usage();
        exit(1);
    }
    
    echo "mengambil gambar...\n";
    
    // PAKAI API TIYANZ
    $url = "https://api.septyandaputra.my.id/api/maker/fakedev.php?img=" . encode($profile) . "&name=" . encode($name) . "&bio=" . encode($bio);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => ['Accept: image/jpeg'],
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($imageData)) {
        fwrite(STDERR, "gagal: " . $httpCode . "\n");
        exit(1);
    }
    
    file_put_contents('fakedev.jpg', $imageData);
    echo "tersimpan: fakedev.jpg\n";
    exit(0);
}

// ========== WEB MODE ==========
header('Content-Type: image/jpeg; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

$credit = ['creator' => 'Nanzz'];

$img = trim($_GET['img'] ?? '');
$name = trim($_GET['name'] ?? '');
$bio = trim($_GET['bio'] ?? '');

if (empty($img) || empty($name) || empty($bio)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'msg' => 'Parameter diperlukan: img, name, bio']);
    exit;
}

// Forward ke API Tiyanz
$url = 'https://api.septyandaputra.my.id/api/maker/fakedev.php?' . http_build_query([
    'img' => $img,
    'name' => $name,
    'bio' => $bio,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => ['Accept: image/jpeg'],
]);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || empty($imageData)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'msg' => 'Gagal fetch dari API asli', 'http_code' => $httpCode]);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
echo $imageData;
?>
