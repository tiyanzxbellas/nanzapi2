<?php
error_reporting(0);
ini_set('display_errors', '0');

// Deskripsi: FakeDev Card Generator
// Parameter: -profile "url" -name "nama" -bio "bio"

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

// Fungsi encode URL (sama seperti di C++)
function encode($v) {
    $out = '';
    for ($i = 0; $i < strlen($v); $i++) {
        $c = $v[$i];
        if (ctype_alnum($c) || $c == '-' || $c == '_' || $c == '.' || $c == '~') {
            $out .= $c;
        } else {
            $out .= '%' . strtoupper(dechex(ord($c)));
        }
    }
    return $out;
}

// Ambil parameter dari GET
$profile = trim($_GET['profile'] ?? '');
$name = trim($_GET['name'] ?? '');
$bio = trim($_GET['bio'] ?? '');

// Validasi parameter (sama seperti di C++)
if (empty($profile) || empty($name) || empty($bio)) {
    echo "pakai: ./fakedev -profile \"url\" -name \"nama\" -bio \"bio\"" . PHP_EOL;
    exit(1);
}

// Buat URL dengan encoding (sama seperti di C++)
$url = "https://api.azbry.com/api/maker/fakedev?img=" . encode($profile) . "&name=" . encode($name) . "&bio=" . encode($bio);

echo "mengambil gambar..." . PHP_EOL;

try {
    // Inisialisasi CURL (mirip http_client di C++)
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);

    // Eksekusi request (mirip client.request di C++)
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    // Cek status code (sama seperti di C++)
    if ($httpCode !== 200 || $error) {
        echo "gagal: " . $httpCode . PHP_EOL;
        if ($error) {
            echo "error: " . $error . PHP_EOL;
        }
        exit(1);
    }

    // Simpan file (mirip file_stream di C++)
    $filename = "fakedev.jpg";
    file_put_contents($filename, $imageData);
    echo "tersimpan: " . $filename . PHP_EOL;

} catch (Exception $e) {
    echo "error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
?>
