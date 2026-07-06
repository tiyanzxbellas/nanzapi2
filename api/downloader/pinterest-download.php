<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Pinterest Search
// Contoh: {"q": "ideas logo"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param limit (5|10|20) Jumlah hasil

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

$query = $_GET['q'] ?? '';
$limit = intval($_GET['limit'] ?? 5);

$allowedLimits = [5, 10, 20];
if (!in_array($limit, $allowedLimits)) $limit = 5;

if (empty($query)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Parameter q wajib diisi']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0');

function curlGet($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $response];
}

function getSession() {
    $result = curlGet('https://id.pinterest.com/', [
        'User-Agent: ' . UA,
        'Accept-Language: en-US,en;q=0.9'
    ]);
    
    // Parse cookies dari header response
    $ch = curl_init('https://id.pinterest.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: ' . UA]);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
    $cookies = implode('; ', $matches[1] ?? []);
    
    preg_match('/csrftoken=([^;]+)/', $cookies, $csrfMatch);
    $csrf = $csrfMatch[1] ?? '';
    
    return ['cookies' => $cookies, 'csrf' => $csrf];
}

try {
    $session = getSession();
    
    if (empty($session['cookies'])) {
        throw new Exception('Gagal mendapatkan session');
    }
    
    $sourceUrl = '/search/pins/?q=' . urlencode($query);
    
    $data = json_encode([
        'options' => [
            'query' => $query,
            'scope' => 'pins',
            'page_size' => $limit,
            'refine_search_with_filters' => true
        ],
        'context' => new stdClass()
    ]);
    
    $url = 'https://id.pinterest.com/resource/BaseSearchResource/get/?source_url=' . urlencode($sourceUrl) . '&data=' . urlencode($data) . '&_=' . (time() * 1000);
    
    $headers = [
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.9',
        'User-Agent: ' . UA,
        'Referer: https://id.pinterest.com' . $sourceUrl,
        'X-Requested-With: XMLHttpRequest',
        'X-App-Version: 6d51d5a',
        'X-Pinterest-Appstate: active',
        'X-Pinterest-PWS-Handler: www/search/[scope].js',
        'X-Pinterest-Source-Url: ' . $sourceUrl,
        'Cookie: ' . $session['cookies']
    ];
    
    if ($session['csrf']) {
        $headers[] = 'X-CSRFToken: ' . $session['csrf'];
    }
    
    $result = curlGet($url, $headers);
    
    if ($result['code'] !== 200 || empty($result['body'])) {
        throw new Exception('API request gagal (HTTP ' . $result['code'] . ')');
    }
    
    $json = json_decode($result['body'], true);
    $payload = $json['resource_response']['data'] ?? [];
    
    if (empty($payload)) {
        throw new Exception('No data');
    }
    
    $arr = isset($payload['results']) ? $payload['results'] : $payload;
    $results = [];
    
    foreach ($arr as $pin) {
        if (empty($pin['id'])) continue;
        
        $image = $pin['images']['orig']['url'] ?? $pin['images']['736x']['url'] ?? null;
        $video = $pin['videos']['video_list']['V_HLSV4']['url'] 
              ?? $pin['videos']['video_list']['V_EXP7']['url'] 
              ?? $pin['videos']['video_list']['V_720P']['url'] 
              ?? null;
        
        $results[] = [
            'title' => $pin['title'] ?? $pin['grid_title'] ?? '',
            'image' => $image,
            'video' => $video,
            'username' => $pin['pinner']['username'] ?? null,
            'full_name' => $pin['pinner']['full_name'] ?? null,
            'pin_url' => 'https://id.pinterest.com/pin/' . $pin['id'] . '/'
        ];
    }
    
    echo json_encode(array_merge(
        $credit,
        [
            'status' => true,
            'result' => [
                'query' => $query,
                'count' => count($results),
                'bookmark' => $payload['bookmark'] ?? null,
                'results' => $results
            ]
        ]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => $e->getMessage()]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>