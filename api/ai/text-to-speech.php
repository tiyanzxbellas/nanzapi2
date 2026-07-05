<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Text to speech Nanas
// Contoh: {"text": "HALO"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// NOTE: API kadang error semua model, cek status result

header('Content-Type: application/json; charset=utf-8');

$text = $_GET['text'] ?? '';

if (empty($text)) {
    echo json_encode([
        'status' => false, 
        'creator' => 'Nanzz',
        'message' => 'Parameter text wajib diisi'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://api.emiliabot.my.id/tools/text-to-speech?text=' . urlencode($text);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    echo json_encode([
        'status' => $data['status'] ?? false,
        'creator' => 'Nanzz',
        'result' => $data['result'] ?? []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => "Gagal mendapatkan response (HTTP {$http_code})"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>