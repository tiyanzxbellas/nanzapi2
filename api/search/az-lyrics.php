<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: AZLyrics - Cari lirik lagu (Rate Limit: 1 request per 30 detik)
// Contoh: {"query":"Duvet"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param query Judul Lagu / Artis

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(60);

$query = $_GET['query'] ?? 'Duvet';

define('BASE', 'https://www.azlyrics.com');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36');
define('CACHE_DIR', sys_get_temp_dir() . '/azlyrics_cache');
define('RATE_LIMIT', 30); // 30 detik

// Bikin folder cache kalo belum ada
if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);

function curlGet($url, $cookieFile, $extraHeaders = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_HTTPHEADER => array_merge([
            'User-Agent: ' . UA,
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
        ], $extraHeaders)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function cleanText($text) {
    $text = str_replace("\r", '', $text);
    $lines = explode("\n", $text);
    $lines = array_map(function($line) {
        return trim(preg_replace('/[ \t]{2,}/', ' ', $line));
    }, $lines);
    $text = implode("\n", $lines);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function decodeHtml($html) {
    return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function getGeoToken($js) {
    if (preg_match('/name["\']\s*,\s*["\']x["\'][\s\S]*?value["\']\s*,\s*["\']([^"\']+)/i', $js, $match)) {
        return $match[1];
    }
    if (preg_match('/setAttribute\(["\']value["\'],\s*["\']([^"\']+)["\']\)/i', $js, $fallback)) {
        return $fallback[1];
    }
    return null;
}

function parseAutocomplete($text) {
    if (preg_match('/^"(.+?)"\s*-\s*(.+)$/', $text, $match)) {
        return [
            'title' => trim($match[1]),
            'artist' => trim($match[2])
        ];
    }
    return ['title' => null, 'artist' => null];
}

function parseLyrics($html) {
    preg_match('/SongName\s*=\s*"([^"]+)"/', $html, $titleMatch);
    preg_match('/ArtistName\s*=\s*"([^"]+)"/', $html, $artistMatch);
    
    preg_match('/<!--\s*Usage of azlyrics\.com content[\s\S]*?-->\s*([\s\S]*?)<\/div>/i', $html, $lyricMatch);
    $rawLyrics = $lyricMatch ? $lyricMatch[1] : '';
    
    $normalizedLyrics = preg_replace('/\r?\n/', '', $rawLyrics);
    $normalizedLyrics = preg_replace('/<br\s*\/?>/i', "\n", $normalizedLyrics);
    $normalizedLyrics = strip_tags($normalizedLyrics);
    
    $lyrics = cleanText(decodeHtml($normalizedLyrics));
    
    return [
        'title' => $titleMatch[1] ?? null,
        'artist' => $artistMatch[1] ?? null,
        'lyrics' => $lyrics
    ];
}

function checkRateLimit() {
    $lockFile = CACHE_DIR . '/rate_limit.lock';
    
    if (file_exists($lockFile)) {
        $lastRequest = (int)file_get_contents($lockFile);
        $elapsed = time() - $lastRequest;
        
        if ($elapsed < RATE_LIMIT) {
            $waitTime = RATE_LIMIT - $elapsed;
            
            // Return info rate limit kalo kena
            $data = [
                'creator' => 'Tiyanz',
                'status' => false,
                'message' => 'Rate limit exceeded',
                'rate_limit' => [
                    'max_request' => '1 per ' . RATE_LIMIT . ' detik',
                    'retry_after' => $waitTime,
                    'retry_after_formatted' => $waitTime . ' detik'
                ]
            ];
            
            $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
            $data = removeKeysRecursive($data, $keysToRemove);
            
            header('HTTP/1.1 429 Too Many Requests');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    
    file_put_contents($lockFile, time());
}

function getCachedToken() {
    $tokenFile = CACHE_DIR . '/token.cache';
    
    if (file_exists($tokenFile)) {
        $data = json_decode(file_get_contents($tokenFile), true);
        // Token berlaku 1 jam
        if ($data && (time() - $data['time']) < 3600) {
            return $data['token'];
        }
    }
    
    return null;
}

function cacheToken($token) {
    $tokenFile = CACHE_DIR . '/token.cache';
    file_put_contents($tokenFile, json_encode([
        'token' => $token,
        'time' => time()
    ]));
}

try {
    // Rate limit check
    checkRateLimit();
    
    $cookieFile = sys_get_temp_dir() . '/azlyrics_' . uniqid() . '.txt';
    
    // Step 1: Get token (cached)
    $token = getCachedToken();
    
    if (!$token) {
        curlGet(BASE . '/', $cookieFile, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ]);
        
        $geoJs = curlGet(BASE . '/geo.js', $cookieFile, [
            'Accept: */*',
            'Referer: ' . BASE . '/'
        ]);
        
        $token = getGeoToken($geoJs);
        if (!$token) throw new Exception('Token x tidak ditemukan dari geo.js');
        
        cacheToken($token);
    }
    
    // Step 2: Search
    $searchUrl = BASE . '/suggest/?q=' . urlencode($query) . '&x=' . urlencode($token);
    $searchRes = curlGet($searchUrl, $cookieFile, [
        'Accept: application/json, text/javascript, */*; q=0.01',
        'X-Requested-With: XMLHttpRequest',
        'Referer: ' . BASE . '/'
    ]);
    
    $json = json_decode($searchRes, true);
    if (!$json) throw new Exception('Response search bukan JSON valid');
    
    $top = $json['songs'][0] ?? $json['lyrics'][0] ?? null;
    if (!$top || !isset($top['url'])) throw new Exception('Hasil tidak ditemukan');
    
    $autocomplete = parseAutocomplete($top['autocomplete'] ?? '');
    
    // Step 3: Get lyrics
    $lyricUrl = str_replace('\\/', '/', $top['url']);
    $lyricPage = curlGet($lyricUrl, $cookieFile, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Referer: ' . BASE . '/'
    ]);
    
    $parsed = parseLyrics($lyricPage);
    if (!$parsed['lyrics']) throw new Exception('Lirik tidak ditemukan atau struktur halaman berubah');
    
    @unlink($cookieFile);
    
    $data = array_merge($credit, [
        'status' => true,
        'input' => $query,
        'top_result' => [
            'title' => $parsed['title'] ?? $autocomplete['title'] ?? '-',
            'artist' => $parsed['artist'] ?? $autocomplete['artist'] ?? '-'
        ],
        'result' => $parsed['lyrics']
    ]);
    
    // Hapus key asli dari API target
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    @unlink($cookieFile ?? '');
    
    $data = array_merge($credit, [
        'status' => false,
        'input' => $query,
        'message' => $e->getMessage(),
        'result' => null
    ]);
    
    // Hapus key asli dari API target
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>