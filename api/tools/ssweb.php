<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: SSWEB Microlink Screenshot
// Contoh: {"url": "https://snaptok.lol", "device": "desktop"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param device (desktop|tablet|mobile) Pilih tipe device screenshot

header('Content-Type: application/json; charset=utf-8');

$targetUrl = $_GET['url'] ?? '';
$device = $_GET['device'] ?? 'desktop';

if (empty($targetUrl)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => 'Parameter url wajib diisi'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Validasi device
$allowedDevices = ['desktop', 'tablet', 'mobile'];
if (!in_array($device, $allowedDevices)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => 'Device tidak valid. Pilih: ' . implode(', ', $allowedDevices)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Set dimensi berdasarkan device
switch ($device) {
    case 'tablet':
        $width = 768;
        $height = 1024;
        break;
    case 'mobile':
        $width = 393;
        $height = 852;
        break;
    default:
        $width = 1920;
        $height = 1080;
}

$encodedUrl = urlencode($targetUrl);
$apiUrl = "https://api.microlink.io/?url={$encodedUrl}&meta=false&screenshot.type=png&screenshot.fullPage=false&viewport.width={$width}&viewport.height={$height}&adblock=true&force=false";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => $error
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

if ($data && ($data['status'] ?? '') === 'success' && isset($data['data']['screenshot'])) {
    $screenshot = $data['data']['screenshot'];
    
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => true,
        'result' => [
            'target_url' => $targetUrl,
            'screenshot_url' => $screenshot['url'] ?? '',
            'format' => $screenshot['type'] ?? 'png',
            'size' => $screenshot['size_pretty'] ?? '',
            'dimensions' => [
                'width' => $screenshot['width'] ?? $width,
                'height' => $screenshot['height'] ?? $height
            ],
            'device' => $device
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => 'Gagal mengambil screenshot'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>