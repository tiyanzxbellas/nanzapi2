<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Instagram Downloader (Debug)
// Contoh: {"url": "https://www.instagram.com/reel/DWoOK9tCW3l/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json');

$credit = 'Tiyanz';
$url = $_GET['url'] ?? '';

if (empty($url)) {
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter url wajib diisi']);
    exit;
}

$apiUrl = 'https://api.ikyyxd.my.id/download/instagram?apikey=kyzz&query=' . urlencode($url);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
curl_close($ch);

echo json_encode([
    'creator' => $credit,
    'debug' => json_decode($response, true)
], JSON_PRETTY_PRINT);
?>