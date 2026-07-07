<?php
error_reporting(0);
ini_set('display_errors', '0');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

// Fungsi untuk encode URL (mirip dengan fungsi encode di C++)
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

// Fungsi untuk menampilkan usage
function usage() {
    echo "pakai: php fakedev.php -profile \"url\" -name \"nama\" -bio \"bio\"\n";
}

// Parsing command line arguments (untuk CLI)
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
    
    // Encode parameters
    $encoded_profile = encode($profile);
    $encoded_name = encode($name);
    $encoded_bio = encode($bio);
    
    // Build URL
    $url = "https://api.azbry.com/api/maker/fakedev?img=" . $encoded_profile . "&name=" . $encoded_name . "&bio=" . $encoded_bio;
    
    echo "mengambil gambar...\n";
    
    // Initialize cURL
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($imageData)) {
        fwrite(STDERR, "gagal: " . $httpCode . "\n");
        if (!empty($error)) {
            fwrite(STDERR, "error: " . $error . "\n");
        }
        exit(1);
    }
    
    // Save to file
    $filename = 'fakedev.jpg';
    if (file_put_contents($filename, $imageData) !== false) {
        echo "tersimpan: " . $filename . "\n";
    } else {
        fwrite(STDERR, "error: Gagal menyimpan file\n");
        exit(1);
    }
    
    exit(0);
}

// Jika diakses melalui web (bukan CLI)
header('Content-Type: image/png; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

$nama   = trim($_GET['nama'] ?? 'Nanzz');
$bio    = trim($_GET['bio'] ?? '@nanzzapi');
$fotourl = trim($_GET['fotourl'] ?? '');

if (empty($fotourl)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'msg' => 'fotourl diperlukan']);
    exit;
}

// Forward ke API asli (versi web)
$url = 'https://api-nanzz.vercel.app/maker/fakedev?' . http_build_query([
    'urlfoto' => $fotourl,
    'text1' => $nama,
    'text2' => $bio,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => ['Accept: image/png'],
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

// Output gambar langsung
header('Content-Type: ' . ($contentType ?: 'image/png'));
echo $imageData;
?>
