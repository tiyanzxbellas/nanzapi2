<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Mendapatkan detail lengkap artis dari SoundCloud (support username/link)
// Contoh: {"url":"https://soundcloud.com/jahseh-onfroy"}
// @param url Link profile SoundCloud (contoh: https://soundcloud.com/jahseh-onfroy)
// @param username Username SoundCloud (contoh: jahseh-onfroy)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

// ========== CONFIG ==========
define('CLIENT_ID', 'KKzJxmw11tYpCs6T24P4uUYhqmjalG6M');
define('BASE_URL', 'https://api-mobi.soundcloud.com');
define('TIMEOUT', 30);
define('MAX_RETRY', 3);

// ========== FUNGSI CURL ==========
function fetchSoundCloud($endpoint, $params = []) {
    $params['client_id'] = CLIENT_ID;
    $params['stage'] = '';
    
    $url = BASE_URL . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/javascript, */*; q=0.1',
            'Content-Type: application/json',
            'Accept-Language: id-ID,id;q=0.9',
            'Connection: keep-alive',
        ],
    ]);
    
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error || $httpCode >= 400) {
        return null;
    }
    
    return json_decode($raw, true);
}

function fetchWithRetry($endpoint, $params = [], $retry = 0) {
    $data = fetchSoundCloud($endpoint, $params);
    if ($data === null && $retry < MAX_RETRY) {
        usleep(500000);
        return fetchWithRetry($endpoint, $params, $retry + 1);
    }
    return $data;
}

// ========== AMBIL PARAMETER ==========
$input = [];
$username = null;
$user_id = null;

// Cek parameter url (link soundcloud)
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $url = trim($_GET['url']);
    // Extract username dari URL
    if (preg_match('/soundcloud\.com\/([^\/\?]+)/', $url, $matches)) {
        $username = $matches[1];
        $input['url'] = $url;
        $input['username'] = $username;
    } else {
        echo json_encode([
            'status' => false,
            'creator' => 'Tiyanz',
            'code' => 400,
            'msg' => '❌ URL SoundCloud tidak valid!',
            'example' => '?url=https://soundcloud.com/jahseh-onfroy'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
// Cek parameter username
else if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = trim($_GET['username']);
    $input['username'] = $username;
}
// Fallback ke default
else {
    $username = 'jahseh-onfroy';
    $input['username'] = $username;
}

// ========== RESOLVE USERNAME TO USER ID ==========
// Coba dapatkan user_id dari endpoint resolve
$resolveData = fetchWithRetry('/resolve', ['url' => 'https://soundcloud.com/' . $username]);

if ($resolveData && isset($resolveData['id'])) {
    $user_id = $resolveData['id'];
    $userInfo = $resolveData;
} else {
    // Fallback: coba dari search
    $searchData = fetchWithRetry('/search/users', ['q' => $username]);
    if ($searchData && isset($searchData['collection'][0]['id'])) {
        $user_id = $searchData['collection'][0]['id'];
        $userInfo = $searchData['collection'][0];
    } else {
        echo json_encode([
            'status' => false,
            'creator' => 'Tiyanz',
            'code' => 404,
            'msg' => '❌ User tidak ditemukan!',
            'input' => $input
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== SCRAPE DATA ==========
// 1. Get Top Tracks
$topTracksData = fetchWithRetry("/users/{$user_id}/toptracks");
$topTracks = isset($topTracksData['collection']) ? $topTracksData['collection'] : [];

// 2. Get All Tracks (1 halaman)
$tracksData = fetchWithRetry("/users/{$user_id}/tracks", ['limit' => 20]);
$allTracks = isset($tracksData['collection']) ? $tracksData['collection'] : [];
$nextHref = isset($tracksData['next_href']) ? $tracksData['next_href'] : null;

// 3. Get Spotlight
$spotlightData = fetchWithRetry("/users/{$user_id}/spotlight");
$spotlight = isset($spotlightData['collection']) ? $spotlightData['collection'] : [];

// ========== PROSES USER INFO ==========
if (empty($userInfo)) {
    $userInfo = [];
}

// ========== PROSES TOP TRACKS ==========
$processedTopTracks = [];
foreach ($topTracks as $track) {
    $processedTopTracks[] = [
        'id' => isset($track['id']) ? $track['id'] : 0,
        'title' => isset($track['title']) ? $track['title'] : 'Unknown',
        'permalink_url' => isset($track['permalink_url']) ? $track['permalink_url'] : '',
        'artwork_url' => isset($track['artwork_url']) ? $track['artwork_url'] : null,
        'duration' => isset($track['duration']) ? floor($track['duration'] / 1000) : 0,
        'playback_count' => isset($track['playback_count']) ? $track['playback_count'] : 0,
        'likes_count' => isset($track['likes_count']) ? $track['likes_count'] : 0,
        'comment_count' => isset($track['comment_count']) ? $track['comment_count'] : 0,
        'reposts_count' => isset($track['reposts_count']) ? $track['reposts_count'] : 0,
        'genre' => isset($track['genre']) ? $track['genre'] : '',
        'release_date' => isset($track['release_date']) ? $track['release_date'] : null,
        'created_at' => isset($track['created_at']) ? $track['created_at'] : '',
        'label_name' => isset($track['label_name']) ? $track['label_name'] : null,
        'policy' => isset($track['policy']) ? $track['policy'] : 'ALLOW',
        'is_playable' => isset($track['policy']) && $track['policy'] === 'ALLOW',
        'album' => isset($track['publisher_metadata']['album_title']) ? $track['publisher_metadata']['album_title'] : '',
        'artist' => isset($track['publisher_metadata']['artist']) ? $track['publisher_metadata']['artist'] : '',
        'explicit' => isset($track['publisher_metadata']['explicit']) ? $track['publisher_metadata']['explicit'] : false,
        'waveform_url' => isset($track['waveform_url']) ? $track['waveform_url'] : '',
    ];
}

// ========== PROSES ALL TRACKS ==========
$processedAllTracks = [];
foreach ($allTracks as $track) {
    $processedAllTracks[] = [
        'id' => isset($track['id']) ? $track['id'] : 0,
        'title' => isset($track['title']) ? $track['title'] : 'Unknown',
        'permalink_url' => isset($track['permalink_url']) ? $track['permalink_url'] : '',
        'artwork_url' => isset($track['artwork_url']) ? $track['artwork_url'] : null,
        'duration' => isset($track['duration']) ? floor($track['duration'] / 1000) : 0,
        'playback_count' => isset($track['playback_count']) ? $track['playback_count'] : 0,
        'likes_count' => isset($track['likes_count']) ? $track['likes_count'] : 0,
        'release_date' => isset($track['release_date']) ? $track['release_date'] : null,
        'created_at' => isset($track['created_at']) ? $track['created_at'] : '',
        'policy' => isset($track['policy']) ? $track['policy'] : 'ALLOW',
        'is_playable' => isset($track['policy']) && $track['policy'] === 'ALLOW',
        'album' => isset($track['publisher_metadata']['album_title']) ? $track['publisher_metadata']['album_title'] : '',
        'artist' => isset($track['publisher_metadata']['artist']) ? $track['publisher_metadata']['artist'] : '',
        'explicit' => isset($track['publisher_metadata']['explicit']) ? $track['publisher_metadata']['explicit'] : false,
    ];
}

// ========== BUILD RESULT ==========
$result = [
    'status' => true,
    'creator' => 'Tiyanz',
    'input' => $input,
    'result' => [
        'user' => [
            'id' => isset($userInfo['id']) ? $userInfo['id'] : $user_id,
            'username' => isset($userInfo['username']) ? $userInfo['username'] : $username,
            'permalink' => isset($userInfo['permalink']) ? $userInfo['permalink'] : $username,
            'permalink_url' => 'https://soundcloud.com/' . $username,
            'avatar_url' => isset($userInfo['avatar_url']) ? $userInfo['avatar_url'] : null,
            'verified' => isset($userInfo['verified']) ? $userInfo['verified'] : false,
            'followers_count' => isset($userInfo['followers_count']) ? $userInfo['followers_count'] : 0,
            'followings_count' => isset($userInfo['followings_count']) ? $userInfo['followings_count'] : 0,
            'likes_count' => isset($userInfo['likes_count']) ? $userInfo['likes_count'] : 0,
            'track_count' => isset($userInfo['track_count']) ? $userInfo['track_count'] : 0,
            'playlist_count' => isset($userInfo['playlist_count']) ? $userInfo['playlist_count'] : 0,
            'city' => isset($userInfo['city']) ? $userInfo['city'] : '',
            'country_code' => isset($userInfo['country_code']) ? $userInfo['country_code'] : null,
            'description' => isset($userInfo['description']) ? $userInfo['description'] : null,
            'badges' => isset($userInfo['badges']) ? $userInfo['badges'] : [],
        ],
        'statistics' => [
            'total_top_tracks' => count($processedTopTracks),
            'total_tracks_shown' => count($processedAllTracks),
            'has_more_tracks' => $nextHref !== null,
            'next_page_url' => $nextHref ? 'https://api-mobi.soundcloud.com' . $nextHref : null,
        ],
        'spotlight' => $spotlight,
        'top_tracks' => $processedTopTracks,
        'latest_tracks' => $processedAllTracks,
    ]
];

// ========== REMOVE KEYS ==========
$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$result = removeKeysRecursive($result, $keysToRemove);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ========== FUNGSI HELPER ==========
function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>