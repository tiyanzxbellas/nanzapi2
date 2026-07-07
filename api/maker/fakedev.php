<?php
error_reporting(0);
ini_set('display_errors', '0');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

// Fungsi encode URL (sama seperti di C++)
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

// Fungsi usage
function usage() {
    echo "pakai: php fakedev.php -profile \"url_foto\" -name \"nama\" -bio \"bio\"\n";
    echo "Contoh: php fakedev.php -profile \"https://example.com/avatar.jpg\" -name \"NanzzCode\" -bio \"Have a Great Code\"\n";
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
    
    // PAKAI API AZBRY (yang aktif)
    $url = "https://api.azbry.com/api/maker/fakedev?img=" . encode($profile) . "&name=" . encode($name) . "&bio=" . encode($bio);
    
    echo "URL: $url\n";
    
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($imageData)) {
        fwrite(STDERR, "gagal: HTTP " . $httpCode . "\n");
        if (!empty($error)) {
            fwrite(STDERR, "error: " . $error . "\n");
        }
        exit(1);
    }
    
    // Simpan sebagai JPG (sesuai API)
    $filename = 'fakedev.jpg';
    if (file_put_contents($filename, $imageData) !== false) {
        echo "tersimpan: " . $filename . "\n";
        echo "Ukuran: " . round(strlen($imageData) / 1024, 2) . " KB\n";
    } else {
        fwrite(STDERR, "error: Gagal menyimpan file\n");
        exit(1);
    }
    
    exit(0);
}

// ========== WEB MODE ==========
// Forward ke API AZBRY (bukan ke nanz)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// Ambil parameter (sesuai dengan API azbry)
$img = trim($_GET['img'] ?? '');
$name = trim($_GET['name'] ?? '');
$bio = trim($_GET['bio'] ?? '');

if (empty($img) || empty($name) || empty($bio)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false, 
        'creator' => 'Nanzz', 
        'msg' => 'Parameter diperlukan: img, name, bio'
    ]);
    exit;
}

// Forward ke API azbry
$url = "https://api.azbry.com/api/maker/fakedev?" . http_build_query([
    'img' => $img,
    'name' => $name,
    'bio' => $bio
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
    echo json_encode([
        'status' => false, 
        'creator' => 'Nanzz', 
        'msg' => 'Gagal fetch dari API', 
        'http_code' => $httpCode
    ]);
    exit;
}

// Output gambar
header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
echo $imageData;
?>
