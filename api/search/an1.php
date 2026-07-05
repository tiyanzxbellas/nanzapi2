<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - AN1.com Search Scraper
// Contoh: {"query": "Roblox"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param query Kata kunci pencarian

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Parameter "query" wajib diisi']]);
    exit;
}

$cookieFile = sys_get_temp_dir() . '/nanzz_an1_search.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://an1.com/index.php?do=search',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'do' => 'search',
        'subaction' => 'search',
        'story' => $query,
        'search_start' => 0,
        'full_search' => 0,
        'result_from' => 1
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,id;q=0.8',
        'Accept-Encoding: gzip, deflate, br',
        'Cache-Control: max-age=0',
        'Content-Type: application/x-www-form-urlencoded',
        'Origin: https://an1.com',
        'Referer: https://an1.com/index.php?do=search',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: same-origin',
        'Sec-Fetch-User: ?1'
    ],
    CURLOPT_ENCODING => 'gzip, deflate, br'
]);

$html = curl_exec($ch);
curl_close($ch);

// Parse total
preg_match('/Found (\d+) (?:apps|games|results)/', $html, $m);
$total = (int)($m[1] ?? 0);

// Parse items - pake DOMDocument biar akurat
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$items = $xpath->query('//div[contains(@class, "item_app")]');
$results = [];

foreach ($items as $item) {
    $linkNodes = $xpath->query('.//div[contains(@class, "name")]//a', $item);
    if ($linkNodes->length === 0) continue;
    
    $link = $linkNodes->item(0);
    $href = $link->getAttribute('href');
    $url = (strpos($href, 'https://') === 0) ? $href : 'https://an1.com' . $href;
    
    // Extract ID dari URL
    $id = '';
    if (preg_match('/\/(\d+)-/', $url, $m)) {
        $id = $m[1];
    }
    
    // Thumbnail - cari di img dulu, kalo ga ada cari di src attribute lain
    $imgNodes = $xpath->query('.//img', $item);
    $thumbnail = '';
    if ($imgNodes->length > 0) {
        $thumbnail = $imgNodes->item(0)->getAttribute('src');
    }
    // Fallback: cari data-src
    if (empty($thumbnail)) {
        if ($imgNodes->length > 0) {
            $thumbnail = $imgNodes->item(0)->getAttribute('data-src');
        }
    }
    
    $devNodes = $xpath->query('.//div[contains(@class, "developer")]', $item);
    $developer = $devNodes->length > 0 ? trim($devNodes->item(0)->textContent) : '';
    
    $ratingNodes = $xpath->query('.//li[contains(@class, "current-rating")]', $item);
    $rating = $ratingNodes->length > 0 ? trim($ratingNodes->item(0)->textContent) : '';
    
    $results[] = [
        'id' => $id,
        'title' => html_entity_decode($link->getAttribute('title') ?: $link->textContent, ENT_QUOTES, 'UTF-8'),
        'url' => $url,
        'thumbnail' => $thumbnail,
        'developer' => $developer,
        'rating' => $rating
    ];
}

// Pages
$pages = 1;
$pageNodes = $xpath->query('//button[contains(@class, "uppercase")]');
foreach ($pageNodes as $btn) {
    if (preg_match('/of (\d+)/', $btn->textContent, $m)) {
        $pages = (int)$m[1];
        break;
    }
}

echo json_encode([
    'status' => true,
    'creator' => 'Nanzz',
    'input' => ['query' => $query],
    'result' => [
        'query' => $query,
        'total' => $total,
        'pages' => $pages,
        'results' => $results
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>