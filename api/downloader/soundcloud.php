<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: SoundCloud Downloader (Output Audio)
// Contoh: {"url": "https://soundcloud.com/xxx-bad-vibes-forever/m00nl1ght"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

set_time_limit(60);

$credit = 'Nanzz';
$url = $_GET['url'] ?? '';

if (empty($url)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter url wajib diisi']);
    exit;
}

define('CLIENT_ID', 'KKzJxmw11tYpCs6T24P4uUYhqmjalG6M');

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Resolve URL
$resolveUrl = 'https://api-mobi.soundcloud.com/resolve?url=' . urlencode($url) . '&client_id=' . CLIENT_ID;
$resolveData = json_decode(curlGet($resolveUrl), true);

if (!$resolveData || empty($resolveData['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Gagal resolve URL']);
    exit;
}

// Cari progressive download URL
$downloadUrl = '';
$transcodings = $resolveData['media']['transcodings'] ?? [];

foreach ($transcodings as $trans) {
    if (($trans['format']['protocol'] ?? '') === 'progressive') {
        $transUrl = $trans['url'] . '?client_id=' . CLIENT_ID;
        $transData = json_decode(curlGet($transUrl), true);
        $downloadUrl = $transData['url'] ?? '';
        if ($downloadUrl) break;
    }
}

if (empty($downloadUrl)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Download URL tidak ditemukan']);
    exit;
}

// Stream audio
$ch = curl_init($downloadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$audio = curl_exec($ch);
curl_close($ch);

header('Content-Type: audio/mpeg');
header('Content-Length: ' . strlen($audio));
header('X-Creator: ' . $credit);
echo $audio;
?>