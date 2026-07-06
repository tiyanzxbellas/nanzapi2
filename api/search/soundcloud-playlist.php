<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Mencari playlist di SoundCloud berdasarkan kata kunci
// Contoh: {"q":"jahseh onfroy","limit":"10"}
// @param q Kata kunci pencarian (wajib)
// @param limit (1|5|10|20|50) Jumlah hasil per halaman (default: 10)
// @param offset (0|10|20|30) Offset untuk pagination (default: 0)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

// ========== CONFIG ==========
define('CLIENT_ID', 'KKzJxmw11tYpCs6T24P4uUYhqmjalG6M');
define('BASE_URL', 'https://api-mobi.soundcloud.com/search/playlists_without_albums');
define('TIMEOUT', 30);
define('MAX_RETRY', 3);

// ========== FUNGSI CURL ==========
function fetchSoundCloud($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER     => [
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

function fetchWithRetry($url, $retry = 0) {
    $data = fetchSoundCloud($url);
    if ($data === null && $retry < MAX_RETRY) {
        usleep(500000);
        return fetchWithRetry($url, $retry + 1);
    }
    return $data;
}

// ========== AMBIL PARAMETER ==========
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Validasi limit
$allowedLimits = [1, 5, 10, 20, 50];
if (!in_array($limit, $allowedLimits)) {
    $limit = 10;
}

// Validasi offset
if ($offset < 0) $offset = 0;

$input = [
    'q' => $query,
    'limit' => $limit,
    'offset' => $offset
];

// ========== VALIDASI ==========
if (empty($query)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'code' => 400,
        'msg' => '❌ Parameter q (query) wajib diisi!',
        'input' => $input,
        'example' => '?q=jahseh+onfroy&limit=10'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== BUILD URL ==========
$url = BASE_URL . '?' . http_build_query([
    'q' => $query,
    'client_id' => CLIENT_ID,
    'limit' => $limit,
    'offset' => $offset,
    'stage' => ''
]);

// ========== FETCH DATA ==========
$data = fetchWithRetry($url);

if (!$data) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'code' => 503,
        'msg' => '❌ Gagal mengambil data dari SoundCloud API',
        'input' => $input
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== EKSTRAK DATA ==========
$collection = isset($data['collection']) ? $data['collection'] : [];
$totalResults = isset($data['total_results']) ? $data['total_results'] : 0;
$nextHref = isset($data['next_href']) ? $data['next_href'] : null;
$queryUrn = isset($data['query_urn']) ? $data['query_urn'] : null;

// ========== PROSES PLAYLIST ==========
$playlists = [];
foreach ($collection as $playlist) {
    // Skip jika bukan playlist
    if (!isset($playlist['kind']) || $playlist['kind'] !== 'playlist') {
        continue;
    }
    
    $tracks = isset($playlist['tracks']) ? $playlist['tracks'] : [];
    $trackCount = isset($playlist['track_count']) ? $playlist['track_count'] : count($tracks);
    
    // Total durasi
    $totalDurationSeconds = isset($playlist['duration']) ? $playlist['duration'] : 0;
    $totalDurationMinutes = floor($totalDurationSeconds / 60);
    $totalDurationHours = floor($totalDurationMinutes / 60);
    $totalDurationMinutesRemaining = $totalDurationMinutes % 60;
    
    // User playlist
    $playlistUser = isset($playlist['user']) ? $playlist['user'] : [];
    
    // Sample tracks (5 track pertama)
    $sampleTracks = [];
    $trackIndex = 0;
    foreach ($tracks as $track) {
        if ($trackIndex >= 5) break;
        if (isset($track['id']) && !isset($track['title'])) continue;
        
        $sampleTracks[] = [
            'id' => isset($track['id']) ? $track['id'] : 0,
            'title' => isset($track['title']) ? $track['title'] : 'Unknown',
            'permalink_url' => isset($track['permalink_url']) ? $track['permalink_url'] : '',
            'artwork_url' => isset($track['artwork_url']) ? $track['artwork_url'] : null,
            'duration' => isset($track['duration']) ? floor($track['duration'] / 1000) : 0,
            'playback_count' => isset($track['playback_count']) ? $track['playback_count'] : 0,
            'likes_count' => isset($track['likes_count']) ? $track['likes_count'] : 0,
            'policy' => isset($track['policy']) ? $track['policy'] : 'ALLOW',
            'is_playable' => isset($track['policy']) && $track['policy'] === 'ALLOW',
            'artist' => isset($track['publisher_metadata']['artist']) ? $track['publisher_metadata']['artist'] : 
                       (isset($track['user']['username']) ? $track['user']['username'] : 'Unknown')
        ];
        $trackIndex++;
    }
    
    // Track IDs
    $trackIds = [];
    foreach ($tracks as $track) {
        if (isset($track['id'])) {
            $trackIds[] = $track['id'];
        }
    }
    
    $playlists[] = [
        'id' => isset($playlist['id']) ? $playlist['id'] : 0,
        'title' => isset($playlist['title']) ? $playlist['title'] : 'Unknown',
        'permalink' => isset($playlist['permalink']) ? $playlist['permalink'] : '',
        'permalink_url' => isset($playlist['permalink_url']) ? $playlist['permalink_url'] : '',
        'artwork_url' => isset($playlist['artwork_url']) ? $playlist['artwork_url'] : null,
        'description' => isset($playlist['description']) ? $playlist['description'] : null,
        'genre' => isset($playlist['genre']) ? $playlist['genre'] : '',
        'created_at' => isset($playlist['created_at']) ? $playlist['created_at'] : '',
        'last_modified' => isset($playlist['last_modified']) ? $playlist['last_modified'] : '',
        'track_count' => $trackCount,
        'total_duration' => [
            'seconds' => $totalDurationSeconds,
            'minutes' => $totalDurationMinutes,
            'hours' => $totalDurationHours,
            'minutes_remaining' => $totalDurationMinutesRemaining,
            'formatted' => ($totalDurationHours > 0 ? $totalDurationHours . 'h ' : '') . $totalDurationMinutesRemaining . 'm'
        ],
        'likes_count' => isset($playlist['likes_count']) ? $playlist['likes_count'] : 0,
        'reposts_count' => isset($playlist['reposts_count']) ? $playlist['reposts_count'] : 0,
        'is_album' => isset($playlist['is_album']) ? $playlist['is_album'] : false,
        'sharing' => isset($playlist['sharing']) ? $playlist['sharing'] : 'private',
        'user' => [
            'id' => isset($playlistUser['id']) ? $playlistUser['id'] : 0,
            'username' => isset($playlistUser['username']) ? $playlistUser['username'] : 'Unknown',
            'permalink' => isset($playlistUser['permalink']) ? $playlistUser['permalink'] : '',
            'permalink_url' => isset($playlistUser['permalink_url']) ? $playlistUser['permalink_url'] : '',
            'avatar_url' => isset($playlistUser['avatar_url']) ? $playlistUser['avatar_url'] : null,
            'verified' => isset($playlistUser['verified']) ? $playlistUser['verified'] : false,
            'followers_count' => isset($playlistUser['followers_count']) ? $playlistUser['followers_count'] : 0,
            'country_code' => isset($playlistUser['country_code']) ? $playlistUser['country_code'] : null,
            'badges' => isset($playlistUser['badges']) ? $playlistUser['badges'] : []
        ],
        'sample_tracks' => $sampleTracks,
        'track_ids' => array_slice($trackIds, 0, 10) // 10 track ID pertama
    ];
}

// ========== BUILD RESULT ==========
$result = [
    'status' => true,
    'creator' => 'Tiyanz',
    'input' => $input,
    'result' => [
        'metadata' => [
            'query' => $query,
            'total_results' => $totalResults,
            'limit' => $limit,
            'offset' => $offset,
            'returned' => count($playlists),
            'has_next_page' => $nextHref !== null,
            'next_href' => $nextHref ? 'https://api-mobi.soundcloud.com' . $nextHref : null,
            'query_urn' => $queryUrn
        ],
        'playlists' => $playlists
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