<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Carbon Code Screenshot
// Contoh: {"text": "console.log(\"Hello World\");", "lang": "javascript"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param lang (auto|javascript|python|php|html|css|json|bash) Bahasa
// @param theme (dracula-pro|monokai|nord|one-dark|material|night-owl) Tema

set_time_limit(60);

$credit = 'Tiyanz';

$code = $_GET['text'] ?? $_GET['code'] ?? '';
$lang = $_GET['lang'] ?? 'auto';
$theme = $_GET['theme'] ?? 'dracula-pro';
$font = $_GET['font'] ?? 'Fira Code';

if (empty($code)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter text wajib diisi']);
    exit;
}

$params = [
    'bg' => 'rgba(226,233,239,1)', 't' => $theme, 'wt' => 'none',
    'l' => $lang, 'ds' => 'false', 'dsyoff' => '20px', 'dsblur' => '68px',
    'wc' => 'true', 'wa' => 'true', 'pv' => '56px', 'ph' => '56px',
    'ln' => 'true', 'fl' => '1', 'fm' => $font, 'fs' => '14px',
    'lh' => '152%', 'si' => 'false', 'es' => '2x', 'wm' => 'false',
    'code' => $code
];

$carbonUrl = 'https://carbon.now.sh/?' . http_build_query($params);
$apiUrl = 'https://api.microlink.io/?url=' . urlencode($carbonUrl) . '&screenshot&element=.export-container&viewport.width=1024&viewport.height=768&meta=false';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$imageUrl = $data['data']['screenshot']['url'] ?? '';

if (empty($imageUrl)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Screenshot gagal']);
    exit;
}

$ch = curl_init($imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$img = curl_exec($ch);
curl_close($ch);

header('Content-Type: image/png');
header('X-Creator: ' . $credit);
echo $img;
?>