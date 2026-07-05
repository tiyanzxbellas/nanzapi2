<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Spotify Audio Streamer
// Contoh: {"q": "multo"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param q Judul Lagu atau URL Spotify

set_time_limit(60);

$query = $_GET['q'] ?? '';

if (empty($query)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'Parameter q wajib diisi']);
    exit;
}

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

// ========== SEARCH (100% SAMA) ==========
define('SECRET', '376136387538459893883312310911992847112448894410210511297108');
define('VERSION', 61);
define('CLIENT_VERSION', '1.2.88.61.ge172202b');
define('UA', 'Mozilla/5.0 (Linux; Android 16; NX729J) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.7499.34 Mobile Safari/537.36');

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateTOTP($tsms) {
    $counter = floor(($tsms / 1000) / 30);
    $buffer = pack('J', $counter);
    $hmac = hash_hmac('sha1', $buffer, SECRET, true);
    $offset = ord($hmac[strlen($hmac) - 1]) & 0xf;
    $code = (unpack('N', substr($hmac, $offset, 4))[1] & 0x7fffffff) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function curlSpotify($url, $method = 'GET', $body = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    $allHeaders = array_merge([
        'User-Agent: ' . UA,
        'Referer: https://open.spotify.com/',
        'Origin: https://open.spotify.com',
        'Accept: application/json'
    ], $headers);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        if (!in_array('Content-Type: application/json', $allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($allHeaders, ['Content-Type: application/json']));
        }
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function getToken() {
    $sts = time();
    $totp = generateTOTP(time() * 1000);
    $totpServer = generateTOTP($sts * 1000);
    
    $token = curlSpotify("https://open.spotify.com/api/token?reason=init&productType=web-player&totp={$totp}&totpServer={$totpServer}&totpVer=" . VERSION);
    if (empty($token['accessToken'])) return null;
    
    $client = curlSpotify('https://clienttoken.spotify.com/v1/clienttoken', 'POST', [
        'client_data' => [
            'client_version' => CLIENT_VERSION,
            'client_id' => $token['clientId'],
            'js_sdk_data' => [
                'device_brand' => 'unknown', 'device_model' => 'unknown',
                'os' => 'linux', 'os_version' => '24.04',
                'device_id' => generateUUID(), 'device_type' => 'computer'
            ]
        ]
    ]);
    
    return [
        'accessToken' => $token['accessToken'],
        'clientToken' => $client['granted_token']['token'] ?? ''
    ];
}

// Cek URL langsung
if (preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $query, $m)) {
    $trackUrl = "https://open.spotify.com/track/" . $m[1];
} else {
    // Search
    $token = getToken();
    if (!$token) {
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'Gagal mendapatkan token']);
        exit;
    }
    
    $searchResult = curlSpotify('https://api-partner.spotify.com/pathfinder/v2/query', 'POST', [
        'variables' => [
            'searchTerm' => $query, 'offset' => 0, 'limit' => 10, 'numberOfTopResults' => 5,
            'includeAudiobooks' => true, 'includeArtistHasConcertsField' => false,
            'includePreReleases' => true, 'includeAuthors' => false, 'includeEpisodeContentRatingsV2' => false
        ],
        'operationName' => 'searchDesktop',
        'extensions' => ['persistedQuery' => ['version' => 1, 'sha256Hash' => '21b3fe49546912ba782db5c47e9ef5a7dbd20329520ba0c7d0fcfadee671d24e']]
    ], [
        'Accept-Language: en', 'App-Platform: WebPlayer',
        'Authorization: Bearer ' . $token['accessToken'],
        'Client-Token: ' . $token['clientToken'], 'Spotify-App-Version: ' . CLIENT_VERSION
    ]);
    
    $searchData = $searchResult['data']['searchV2'] ?? [];
    $trackItems = $searchData['tracksV2']['items'] ?? [];
    
    if (empty($trackItems)) {
        $topItems = $searchData['topResultsV2']['itemsV2'] ?? [];
        foreach ($topItems as $item) {
            if (($item['item']['__typename'] ?? '') === 'TrackResponseWrapper') {
                $trackItems[] = $item;
            }
        }
    }
    
    if (empty($trackItems)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'Lagu tidak ditemukan']);
        exit;
    }
    
    $t = $trackItems[0]['item']['data'] ?? $trackItems[0]['data'] ?? $trackItems[0];
    $p = explode(':', $t['uri'] ?? '');
    $trackUrl = "https://open.spotify.com/track/" . ($p[2] ?? '');
}

// ========== DOWNLOAD VIA MUSICFAB ==========
$ch = curl_init('https://musicfab.io/api/spotify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['url' => $trackUrl]),
    CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
        'Accept: */*', 'Content-Type: application/json',
        'Origin: https://musicfab.io', 'Referer: https://musicfab.io/'
    ]
]);
$res = curl_exec($ch); curl_close($ch);
$result = json_decode($res, true);
$downloadUrl = $result['data']['metadata']['download'] ?? '';

if (!$downloadUrl) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'Gagal download']);
    exit;
}

// ========== STREAM AUDIO ==========
header('Content-Type: audio/mpeg');
$ch = curl_init($downloadUrl);
curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 0,
    CURLOPT_WRITEFUNCTION => function($ch, $data) { echo $data; return strlen($data); }]);
curl_exec($ch); curl_close($ch);
?>