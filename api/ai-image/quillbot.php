<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Quillbot AI Image Generator - Output Gambar
// Contoh: {"text":"Bugatti"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Prompt Gambar

header('Content-Type: image/jpeg');

$credit = ['creator' => 'Tiyanz'];
set_time_limit(120);

$text = $_GET['text'] ?? 'Bugatti';

if (!$text) {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter text diperlukan']), JSON_PRETTY_PRINT);
    exit;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function curlReq($url, $cookieFile, $post = false, $data = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_HTTPHEADER => array_merge([
            'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36'
        ], $headers)
    ]);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $res = curl_exec($ch); curl_close($ch);
    return $res;
}

$cookieFile = sys_get_temp_dir() . '/quillbot_img_' . uniqid() . '.txt';

// Visit homepage
curlReq('https://quillbot.com/', $cookieFile);

// Generate
$res = curlReq('https://quillbot.com/api/raven/generate/image', $cookieFile, true, [
    'prompt' => $text, 'category' => 'Auto', 'aspectRatio' => '1:1', 'promptId' => 'image/generate-image'
], [
    'Content-Type: application/json', 'Origin: https://quillbot.com',
    'Referer: https://quillbot.com/ai-image-generator/i/' . uuid(),
    'webapp-version: 42.51.6', 'qb-product: IMAGE-GENERATOR', 'platform-type: webapp'
]);

$data = json_decode($res, true);
$imageUrl = $data['data']['images'][0]['downloadUrl'] ?? '';

@unlink($cookieFile);

if ($imageUrl) {
    $ch = curl_init($imageUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false]);
    $img = curl_exec($ch);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if ($img) {
        header('Content-Type: ' . ($type ?: 'image/jpeg'));
        header('Content-Length: ' . strlen($img));
        echo $img;
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal generate gambar']), JSON_PRETTY_PRINT);
?>