<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: TikTok Search
// Contoh: {"q": "aesthetic videos"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param limit (1|3|5|10) Jumlah hasil

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

$query = $_GET['q'] ?? '';
$limit = intval($_GET['limit'] ?? 5);

$allowedLimits = [1, 3, 5, 10];
if (!in_array($limit, $allowedLimits)) $limit = 5;

if (empty($query)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Parameter q wajib diisi']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://www.tikwm.com/api/feed/search?keywords=' . urlencode($query) . '&count=' . $limit . '&cursor=0&hd=1';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: en-US,en;q=0.9',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',
    'Referer: https://www.tikwm.com/'
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

$json = json_decode($response, true);

if (!$json || ($json['code'] ?? -1) !== 0) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => $json['msg'] ?? 'Request failed']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$videos = $json['data']['videos'] ?? [];
$results = [];

foreach ($videos as $v) {
    $images = [];
    if (isset($v['images']) && is_array($v['images'])) {
        foreach ($v['images'] as $img) {
            $imgUrl = is_string($img) ? $img : ($img['url'] ?? $img['display_image']['url_list'][0] ?? null);
            if ($imgUrl) $images[] = $imgUrl;
        }
    }
    $isSlideshow = !empty($images);
    
    $results[] = [
        'id' => $v['video_id'] ?? $v['aweme_id'] ?? null,
        'type' => $isSlideshow ? 'slideshow' : 'video',
        'description' => $v['title'] ?? '',
        'video_url' => !$isSlideshow ? ($v['hdplay'] ?? $v['play'] ?? null) : null,
        'video_watermark' => !$isSlideshow ? ($v['wmplay'] ?? null) : null,
        'images' => $isSlideshow ? $images : null,
        'cover' => $v['cover'] ?? $v['origin_cover'] ?? null,
        'duration' => intval($v['duration'] ?? 0),
        'likes' => intval($v['digg_count'] ?? 0),
        'comments' => intval($v['comment_count'] ?? 0),
        'shares' => intval($v['share_count'] ?? 0),
        'views' => intval($v['play_count'] ?? 0),
        'downloads' => intval($v['download_count'] ?? 0),
        'created_at' => isset($v['create_time']) ? date('c', $v['create_time']) : null,
        'music' => [
            'title' => $v['music_info']['title'] ?? null,
            'author' => $v['music_info']['author'] ?? null,
            'url' => $v['music'] ?? $v['music_info']['play'] ?? null
        ],
        'author' => [
            'id' => $v['author']['id'] ?? null,
            'username' => $v['author']['unique_id'] ?? null,
            'nickname' => $v['author']['nickname'] ?? '',
            'avatar' => $v['author']['avatar'] ?? null
        ],
        'post_url' => ($v['author']['unique_id'] ?? false) && ($v['video_id'] ?? $v['aweme_id'] ?? false)
            ? 'https://www.tiktok.com/@' . $v['author']['unique_id'] . '/video/' . ($v['video_id'] ?? $v['aweme_id'])
            : null
    ];
}

echo json_encode(array_merge(
    $credit,
    [
        'status' => true,
        'result' => [
            'query' => $query,
            'count' => count($results),
            'hasMore' => !!($json['data']['hasMore'] ?? false),
            'videos' => $results
        ]
    ]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>