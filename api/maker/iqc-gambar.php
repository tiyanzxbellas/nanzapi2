<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: iPhone Quoted V2
// Contoh: {"text":"wikwokdetok","url":"https://www.upload.ee/image/19400325/images.webp","carrier":"XL","battery":"88","signal":"4","sender":"other","read":"true"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Isi pesan
// @param url URL gambar
// @param carrier Nama operator
// @param battery Persentase baterai
// @param signal Kekuatan sinyal
// @param sender (self|other) Posisi Buble
// @param read (true|false) Status Dibaca

set_time_limit(60);

$credit = ['creator' => 'Nanzz'];

$text = trim($_GET['text'] ?? '');
$image = trim($_GET['url'] ?? '');
$carrier = trim($_GET['carrier'] ?? 'INDOSAT OORE...');
$battery = (int)($_GET['battery'] ?? 88);
$signal = (int)($_GET['signal'] ?? 4);
$sender = in_array($_GET['sender'] ?? 'other', ['self', 'other']) ? $_GET['sender'] : 'other';
$read = filter_var($_GET['read'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

if (!$text) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Nanzz', 'status' => false, 'message' => 'Parameter text diperlukan'], JSON_PRETTY_PRINT);
    exit;
}

$payload = [
    'sender' => $sender,
    'message' => $text,
    'imageUrl' => $image,
    'timestamp' => date('H.i'),
    'time' => date('H.i'),
    'status' => ['carrierName' => $carrier, 'batteryPercentage' => $battery, 'signalStrength' => $signal, 'wifi' => true],
    'backgroundUrl' => '',
    'readStatus' => $read,
    'emojiStyle' => 'apple'
];

$ch = curl_init('https://brat.siputzx.my.id/v2/iphone-quoted');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: image/png'],
    CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 60
]);
$result = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => 'Nanzz', 'status' => false, 'message' => 'Gagal generate gambar'], JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'image/png'));
echo $result;
?>