<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: TikTok Image Search
// Contoh: {"q": "aesthetic wallpaper"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Parameter q wajib diisi']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://api.cuki.biz.id/api/search/tiktokfoto?apikey=cuki-x&query=' . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'API request gagal (HTTP ' . $http_code . ')']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);
$results = $data['data']['results'] ?? [];

if (empty($results)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Tidak ada hasil']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Pick random
$randomItem = $results[array_rand($results)];

$images = $randomItem['images'] ?? [];
$title = $randomItem['title'] ?? 'Result';

echo json_encode(array_merge(
    $credit,
    [
        'status' => true,
        'result' => [
            'title' => $title,
            'images' => $images,
            'total_images' => count($images)
        ]
    ]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>