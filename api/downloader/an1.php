<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Scraper Detail + Download AN1.com by ID
// Contoh: {"id": "3528"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param id ID Game AN1.com

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

// ========== KODE UTAMA ==========
function scrapeURL($url) {
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $userAgents[array_rand($userAgents)],
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Referer: https://www.google.com/',
            'Origin: https://an1.com'
        ],
        CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/nanzz_an1_cookie.txt',
        CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/nanzz_an1_cookie.txt',
        CURLOPT_ENCODING => 'gzip, deflate, br'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) {
        return ['error' => true, 'message' => "HTTP $httpCode: $error"];
    }
    
    clearstatcache();
    opcache_reset();
    usleep(rand(50000, 200000));
    
    return ['error' => false, 'html' => $response];
}

function parseMainPage($html, $id) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    $result = [
        'id' => $id,
        'title' => '',
        'version' => '',
        'developer' => '',
        'category' => '',
        'description' => '',
        'thumbnail' => '',
        'screenshots' => [],
        'features' => [],
        'mod_info' => '',
        'google_play_url' => '',
        'download_page_url' => 'https://an1.com/file_' . $id . '-dw.html',
        'download_url' => '',
        'size' => '',
        'android_requirement' => ''
    ];
    
    // Title
    $titleNodes = $xpath->query('//h1');
    if ($titleNodes->length > 0) {
        $result['title'] = trim($titleNodes->item(0)->textContent);
    }
    
    // Description
    $descNodes = $xpath->query('//div[contains(@class, "full-text")]//p | //article//p | //div[contains(@class, "description")]//p');
    if ($descNodes->length > 0) {
        $descText = '';
        foreach ($descNodes as $p) {
            $descText .= trim($p->textContent) . ' ';
        }
        $result['description'] = trim($descText);
    }
    
    // Meta description
    if (empty($result['description'])) {
        $metaNodes = $xpath->query('//meta[@name="description"]');
        if ($metaNodes->length > 0) {
            $result['description'] = $metaNodes->item(0)->getAttribute('content');
        }
    }
    
    // Thumbnail
    $imgNodes = $xpath->query('//div[contains(@class, "app-icon")]//img | //div[contains(@class, "img-block")]//img | //img[contains(@class, "app_icon")]');
    if ($imgNodes->length > 0) {
        $result['thumbnail'] = $imgNodes->item(0)->getAttribute('src');
    }
    
    // Screenshots
    $screenNodes = $xpath->query('//div[contains(@class, "screenshots")]//img | //div[contains(@class, "screen")]//img');
    foreach ($screenNodes as $img) {
        $src = $img->getAttribute('src');
        if (!empty($src) && $src !== $result['thumbnail']) {
            $result['screenshots'][] = $src;
        }
    }
    
    // Version
    if (preg_match('/Version:\s*<\/b>\s*([\d\.]+)/', $html, $m)) {
        $result['version'] = $m[1];
    }
    if (empty($result['version']) && preg_match('/(\d+\.\d+(?:\.\d+)?)\s*(?:Apk|APK|apk)/', $html, $m)) {
        $result['version'] = $m[1];
    }
    
    // Developer
    if (preg_match('/Developer:\s*<\/b>\s*<a[^>]*>([^<]+)</', $html, $m)) {
        $result['developer'] = trim($m[1]);
    }
    
    // Category
    if (preg_match('/Category:\s*<\/b>\s*<a[^>]*>([^<]+)</', $html, $m)) {
        $result['category'] = trim($m[1]);
    }
    
    // Google Play URL
    if (preg_match('/href="(https:\/\/play\.google\.com\/store\/apps\/details\?id=[^"]+)"/', $html, $m)) {
        $result['google_play_url'] = $m[1];
    }
    
    // MOD Info
    if (preg_match('/MOD(?: Features| Info)?:?\s*<\/b>(.*?)(?:<br|<p|<\/p|\n\n)/s', $html, $m)) {
        $result['mod_info'] = trim(strip_tags($m[1]));
    }
    
    // Features
    $featureNodes = $xpath->query('//div[contains(@class, "features")]//li | //ul[contains(@class, "mod-features")]//li');
    foreach ($featureNodes as $li) {
        $result['features'][] = trim($li->textContent);
    }
    
    return $result;
}

function parseDownloadPage($html, $id) {
    $result = [
        'download_url' => '',
        'size' => '',
        'android_requirement' => '',
        'an1_store_url' => '',
        'pc_emulator_url' => '',
        'title' => '',
        'version' => '',
        'thumbnail' => '',
        'timer_seconds' => 0
    ];
    
    // Download URL
    if (preg_match('/<a[^>]*id="pre_download"[^>]*href="([^"]+)"[^>]*>/', $html, $m)) {
        $result['download_url'] = $m[1];
    }
    
    // Size
    if (preg_match('/<a[^>]*id="pre_download"[^>]*>.*?\(([\d\.]+\s*(?:Mb|MB|GB|Gb|KB|Kb))\).*?<\/a>/s', $html, $m)) {
        $result['size'] = $m[1];
    }
    
    // AN1 Store
    if (preg_match('/<a[^>]*class="[^"]*an1-mobile-download[^"]*"[^>]*href="([^"]+)"[^>]*>/', $html, $m)) {
        $result['an1_store_url'] = $m[1];
    }
    
    // PC Emulator
    if (preg_match('/<a[^>]*href="(https:\/\/www\.ldplayer\.net[^"]+)"[^>]*>.*?Play on PC/s', $html, $m)) {
        $result['pc_emulator_url'] = $m[1];
    }
    
    // Title
    if (preg_match('/<h1[^>]*class="[^"]*title[^"]*"[^>]*>(.*?)<\/h1>/', $html, $m)) {
        $result['title'] = trim($m[1]);
        if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $result['title'], $v)) {
            $result['version'] = $v[1];
        }
    }
    
    // Thumbnail
    if (preg_match('/<div[^>]*class="[^"]*box-file-img[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"[^>]*>/s', $html, $m)) {
        $result['thumbnail'] = $m[1];
    }
    
    // Android requirement
    if (preg_match('/<li[^>]*id="a_ver"[^>]*>(.*?)<\/li>/s', $html, $m)) {
        $text = strip_tags($m[1]);
        if (preg_match('/Android\s*([\d\.]+)\s*\+?/', $text, $av)) {
            $result['android_requirement'] = 'Android ' . $av[1] . '+';
        }
    }
    
    // Timer
    if (preg_match('/countdown\((\d+)\)/', $html, $m)) {
        $result['timer_seconds'] = (int)$m[1];
    }
    
    return $result;
}

// ========== AMBIL PARAMETER ==========
$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id) || !is_numeric($id)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'input' => ['id' => $id],
        'result' => ['error' => 'Parameter "id" wajib diisi dengan angka']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== SCRAPE MAIN PAGE ==========
$mainUrl = 'https://an1.com/' . $id . '-game.html';

// Coba beberapa pattern URL
$mainPatterns = [
    'https://an1.com/' . $id . '-game.html',
    'https://an1.com/' . $id . '-mod.html',
    'https://an1.com/' . $id . '-apk.html',
];

$mainHtml = null;
$finalMainUrl = '';

foreach ($mainPatterns as $patternUrl) {
    $result = scrapeURL($patternUrl);
    if (!$result['error']) {
        $mainHtml = $result['html'];
        $finalMainUrl = $patternUrl;
        break;
    }
}

// Jika pattern gagal, coba cari dari download page dulu
if (!$mainHtml) {
    $dwUrl = 'https://an1.com/file_' . $id . '-dw.html';
    $dwResult = scrapeURL($dwUrl);
    
    if ($dwResult['error']) {
        echo json_encode([
            'status' => false,
            'creator' => 'Nanzz',
            'input' => ['id' => $id],
            'result' => ['error' => 'Game dengan ID ' . $id . ' tidak ditemukan']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Ambil URL main page dari tombol back
    if (preg_match('/<a[^>]*class="[^"]*btn-back[^"]*"[^>]*href="([^"]+)"[^>]*>/', $dwResult['html'], $m)) {
        $backUrl = $m[1];
        if (strpos($backUrl, 'https://') !== 0) {
            $backUrl = 'https://an1.com' . $backUrl;
        }
        $mainResult = scrapeURL($backUrl);
        if (!$mainResult['error']) {
            $mainHtml = $mainResult['html'];
            $finalMainUrl = $backUrl;
        }
    }
}

// ========== PARSE MAIN PAGE ==========
$detailData = [];
if ($mainHtml) {
    $detailData = parseMainPage($mainHtml, $id);
    $detailData['main_page_url'] = $finalMainUrl;
}

// ========== SCRAPE DOWNLOAD PAGE ==========
$downloadUrl = 'https://an1.com/file_' . $id . '-dw.html';
$downloadResult = scrapeURL($downloadUrl);
$downloadData = [];

if (!$downloadResult['error']) {
    $downloadData = parseDownloadPage($downloadResult['html'], $id);
}

// ========== GABUNG DATA ==========
$mergedResult = array_merge($detailData, $downloadData);
$mergedResult['id'] = $id;

// Bersihin duplikat
$mergedResult['title'] = !empty($downloadData['title']) ? $downloadData['title'] : $detailData['title'];
$mergedResult['version'] = !empty($downloadData['version']) ? $downloadData['version'] : $detailData['version'];
$mergedResult['thumbnail'] = !empty($downloadData['thumbnail']) ? $downloadData['thumbnail'] : $detailData['thumbnail'];

$data = [
    'status' => true,
    'creator' => 'Nanzz',
    'input' => ['id' => $id],
    'result' => $mergedResult
];

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