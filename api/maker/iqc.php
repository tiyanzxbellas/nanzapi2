<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: iPhone Quoted Generator
// Contoh: {"text":"Hai dunia"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Isi pesan
// @param carrier Nama operator
// @param battery Persentase baterai
// @param signal Kekuatan sinyal

$text = trim($_GET['text'] ?? '');
$carrier = trim($_GET['carrier'] ?? 'XL');
$battery = trim($_GET['battery'] ?? '100');
$signal = trim($_GET['signal'] ?? '4');

if (!$text) {
    header('Content-Type: application/json');

    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Parameter text diperlukan'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

$url = 'https://brat.siputzx.my.id/iphone-quoted?' . http_build_query([
    'messageText' => $text,
    'carrierName' => $carrier,
    'batteryPercentage' => $battery,
    'signalStrength' => $signal,
    'emojiStyle' => 'apple'
]);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);

$image = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if (!$image || $httpCode != 200) {

    header('Content-Type: application/json');

    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal mengambil gambar'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

header('Content-Type: image/png');

echo $image;