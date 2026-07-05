<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: SoundCloud Search
// Contoh: {"q": "moonlight"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param limit (5|10|15|20) Jumlah hasil

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$query = $_GET['q'] ?? '';
$limit = intval($_GET['limit'] ?? 10);

$allowedLimits = [5, 10, 15, 20];
if (!in_array($limit, $allowedLimits)) $limit = 10;

if (empty($query)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter q wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$clientId = 'KKzJxmw11tYpCs6T24P4uUYhqmjalG6M';
$url = 'https://api-mobi.soundcloud.com/search?q=' . urlencode($query) . '&client_id=' . $clientId . '&stage=';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/javascript, */*; q=0.1',
    'Content-Type: application/json',
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'API request gagal (HTTP ' . $http_code . ')'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);
$collection = $data['collection'] ?? [];

$results = [];
foreach ($collection as $item) {
    if ($item['kind'] !== 'track') continue;
    
    // Cari progressive download URL
    $downloadUrl = '';
    $transcodings = $item['media']['transcodings'] ?? [];
    foreach ($transcodings as $trans) {
        if (($trans['format']['protocol'] ?? '') === 'progressive') {
            $downloadUrl = $trans['url'] ?? '';
            break;
        }
    }
    
    $results[] = [
        'id' => $item['id'] ?? '',
        'title' => $item['title'] ?? '',
        'artist' => $item['user']['username'] ?? '',
        'artist_full' => $item['user']['full_name'] ?? '',
        'duration' => round(($item['duration'] ?? 0) / 1000, 1) . 's',
        'artwork' => $item['artwork_url'] ?? '',
        'permalink' => $item['permalink_url'] ?? '',
        'plays' => $item['playback_count'] ?? 0,
        'likes' => $item['likes_count'] ?? 0,
        'download_url' => $downloadUrl,
        'created_at' => $item['created_at'] ?? ''
    ];
    
    if (count($results) >= $limit) break;
}

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => [
        'query' => $query,
        'total' => $data['total_results'] ?? 0,
        'count' => count($results),
        'tracks' => $results
    ]
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>