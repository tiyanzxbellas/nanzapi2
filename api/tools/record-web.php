<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Website Screenshot (BentoScreen)
// Contoh: {"url":"https://api-nanzz.my.id/docs"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL website
// @param device (desktop_hd|desktop_fhd|desktop_4k|desktop_wide|laptop_13|laptop_15|macbook_air|macbook_pro|ipad|ipad_pro|ipad_mini|samsung_tab|iphone_se|iphone_14|iphone_14_pro|iphone_15_pro|samsung_s24|pixel_8|xiaomi_14) Device
// @param scroll (true|false) Scroll page
// @param dark_mode (true|false) Dark mode

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$url = $_GET['url'] ?? '';
$device = $_GET['device'] ?? 'desktop_fhd';
$scroll = ($_GET['scroll'] ?? 'true') === 'true';
$darkMode = ($_GET['dark_mode'] ?? 'false') === 'true';

if (empty($url)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter url wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$validDevices = [
    'desktop_hd',
    'desktop_fhd',
    'desktop_4k',
    'desktop_wide',
    'laptop_13',
    'laptop_15',
    'macbook_air',
    'macbook_pro',
    'ipad',
    'ipad_pro',
    'ipad_mini',
    'samsung_tab',
    'iphone_se',
    'iphone_14',
    'iphone_14_pro',
    'iphone_15_pro',
    'samsung_s24',
    'pixel_8',
    'xiaomi_14'
];

if (!in_array($device, $validDevices)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Device tidak valid'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = [
    'url' => $url,
    'device' => $device,
    'duration_ms' => 8000,
    'scroll' => $scroll,
    'dark_mode' => $darkMode,
    'wait_ms' => 1000
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'https://shinana-bentosnap.hf.space/api/record',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0'
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Screenshot gagal (HTTP ' . $http_code . ')'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

$keysToRemove = [
    'creator',
    'Creator',
    'author',
    'Author'
];

$data = removeKeysRecursive($data, $keysToRemove);

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => $data
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;

    foreach ($keysToRemove as $key) {
        unset($array[$key]);
    }

    foreach ($array as &$value) {
        if (is_array($value)) {
            $value = removeKeysRecursive($value, $keysToRemove);
        }
    }

    return $array;
}
?>