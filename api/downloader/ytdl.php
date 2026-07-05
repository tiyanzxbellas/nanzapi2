<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - YouTube Video Downloader (Vidssave API Wrapper)
// Contoh: {"url": "https://youtu.be/bgBq9rYDN_8"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url Text Input - URL YouTube video (youtube.com/watch?v= atau youtu.be/)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== KODE UTAMA ==========
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

// Validasi input
if (empty($url)) {
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'URL YouTube diperlukan. Contoh: ?url=https://youtu.be/bgBq9rYDN_8'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== EKSTRAK VIDEO ID ==========
$video_id = '';
if (preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/', $url, $m)) {
    $video_id = $m[1];
} elseif (preg_match('/watch\?v=([A-Za-z0-9_-]+)/', $url, $m)) {
    $video_id = $m[1];
} elseif (preg_match('/\/embed\/([A-Za-z0-9_-]+)/', $url, $m)) {
    $video_id = $m[1];
} elseif (preg_match('/\/shorts\/([A-Za-z0-9_-]+)/', $url, $m)) {
    $video_id = $m[1];
}

if (empty($video_id)) {
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal mengekstrak Video ID dari URL',
        'input' => ['url' => $url]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== API CONFIG ==========
$api_url = 'https://api.vidssave.com/api/contentsite_api/media/parse';
$auth = '20250901majwlqo';
$domain = 'api-ak.vidssave.com';

// ========== REQUEST PAYLOAD ==========
$post_data = http_build_query([
    'auth' => $auth,
    'domain' => $domain,
    'origin' => 'source',
    'link' => "https://youtu.be/{$video_id}"
]);

// ========== USER AGENT RANDOM ==========
$user_agents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Linux; Android 14; SM-S24 Ultra) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
];
$ua = $user_agents[array_rand($user_agents)];

// ========== RETRY LOGIC ==========
$max_retries = 3;
$response = false;
$http_code = 0;

for ($retry = 0; $retry < $max_retries; $retry++) {
    if ($retry > 0) usleep(rand(50000, 200000));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9,id;q=0.8',
            'Origin: https://api.vidssave.com',
            'Referer: https://api.vidssave.com/'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false && $http_code === 200) break;
}

if ($response === false || $http_code !== 200) {
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal menghubungi Vidssave API',
        'http_code' => $http_code
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$api_data = json_decode($response, true);

if (!$api_data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal parsing response API'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($api_data['status']) || $api_data['status'] != 1) {
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal mendapatkan data video'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== EKSTRAK DATA ==========
$result_data = $api_data['data'];

// Info video
$title = $result_data['title'] ?? '';
$thumbnail = $result_data['thumbnail'] ?? '';
$duration = $result_data['duration'] ?? 0;
$video_id_final = $result_data['id'] ?? $video_id;

// Author info
$author_name = $result_data['user_item']['nickname'] ?? '';
$author_uid = $result_data['user_item']['uid'] ?? '';

// Stats
$likes = $result_data['like_count'] ?? 0;
$comments = $result_data['comment_count'] ?? 0;

// ========== EKSTRAK MEDIA RESOURCES ==========
$video_formats = [];
$audio_formats = [];

if (isset($result_data['media'])) {
    foreach ($result_data['media'] as $media) {
        $type = $media['type'] ?? '';
        $resources = $media['resources'] ?? [];
        
        foreach ($resources as $res) {
            $format = $res['format'] ?? '';
            $quality = $res['quality'] ?? '';
            $download_url = $res['download_url'] ?? '';
            $size = $res['size'] ?? 0;
            $original_format = $res['original_format'] ?? '';
            $download_mode = $res['download_mode'] ?? '';
            
            $item = [
                'quality' => $quality,
                'format' => $format,
                'type' => $type,
                'size' => $size,
                'size_mb' => $size > 0 ? round($size / 1048576, 2) . ' MB' : 'N/A',
                'download_url' => !empty($download_url) ? $download_url : null,
                'download_mode' => $download_mode ?: 'popup',
                'original_format' => $original_format
            ];
            
            if ($type === 'video') {
                $video_formats[] = $item;
            } elseif ($type === 'audio') {
                $audio_formats[] = $item;
            }
        }
    }
}

// Urutkan berdasarkan quality
usort($video_formats, function($a, $b) { return $a['quality'] <=> $b['quality']; });

// ========== SUSUN RESPONSE ==========
$data = [
    'creator' => 'Nanzz',
    'status' => true,
    'input' => [
        'url' => $url,
        'video_id' => $video_id_final
    ],
    'result' => [
        'video_id' => $video_id_final,
        'title' => $title,
        'thumbnail' => $thumbnail,
        'duration' => $duration,
        'duration_formatted' => gmdate('i:s', $duration),
        'author' => [
            'name' => $author_name,
            'uid' => $author_uid,
            'channel_url' => !empty($author_uid) ? "https://youtube.com/channel/{$author_uid}" : ''
        ],
        'stats' => [
            'likes' => $likes,
            'comments' => $comments
        ],
        'video_formats_count' => count($video_formats),
        'video_formats' => $video_formats,
        'audio_formats_count' => count($audio_formats),
        'audio_formats' => $audio_formats,
        'youtube_url' => "https://youtu.be/{$video_id_final}",
        'watch_url' => "https://youtube.com/watch?v={$video_id_final}",
        'scraped_at' => date('Y-m-d H:i:s')
    ]
];

// Cleanup
$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);
$data['creator'] = 'Nanzz';

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>