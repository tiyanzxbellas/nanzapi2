<?php
error_reporting(0);
ini_set('display_errors', '0');
/*
// Deskripsi: Cek Paket XL
// Contoh: {"number": "6283124609929"}
*/

// Creator: Tiyanz
header('Content-Type: application/json');

$number = isset($_GET['number']) ? $_GET['number'] : '';

if (empty($number)) {
    echo json_encode([
        "success" => false,
        "message" => "Parameter 'number' wajib diisi",
        "example" => "xlku.php?number=08123456789"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validasi nomor HP (opsional)
if (!preg_match('/^[0-9]{10,13}$/', $number)) {
    echo json_encode([
        "success" => false,
        "message" => "Format nomor tidak valid. Gunakan 10-13 digit angka"
    ], JSON_PRETTY_PRINT);
    exit;
}

$url = "https://xl-ku.my.id/end.php?check=package&number=" . urlencode($number);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode([
        "success" => false,
        "message" => "Curl Error: " . $curlError
    ], JSON_PRETTY_PRINT);
} elseif ($httpCode == 200) {
    // Kirim response asli dari API
    echo $response;
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil data. HTTP Code: " . $httpCode
    ], JSON_PRETTY_PRINT);
}
?>