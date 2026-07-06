<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - VideyStream Video List Scraper
// Contoh: {"sort": "terbaru", "page": 1}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param sort (terbaru|trending|lama) Urutan
// @param page Halaman

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];
set_time_limit(20);

$sort = trim($_GET['sort'] ?? 'terbaru');
$page = max(1, (int)($_GET['page'] ?? 1));

$data = [
    'status' => false,
    'creator' => 'Tiyanz',
    'input' => ['sort' => $sort, 'page' => $page],
    'result' => null
];

// Build URL
$url = 'https://videystream.vip/';
$params = [];
if ($page > 1) $params['page'] = $page;
if ($sort !== 'terbaru') $params['sort'] = $sort;
if (!empty($params)) $url .= '?' . http_build_query($params);

// Fetch
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 20,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($html)) {
    $data['result'] = ['msg' => 'Gagal fetch halaman', 'http_code' => $httpCode];
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Parse
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$videos = [];
$cards = $xpath->query('//div[contains(@class, "video-card")]');

foreach ($cards as $card) {
    $link = $xpath->query('.//a[contains(@href, "watch.php?id=")]', $card)->item(0);
    $img = $xpath->query('.//img', $card)->item(0);
    $titleEl = $xpath->query('.//div[contains(@class, "video-title")]', $card)->item(0);
    $viewsEl = $xpath->query('.//span/i[contains(@class, "fa-eye")]', $card)->item(0);
    $durationEl = $xpath->query('.//span[contains(@class, "duration")]', $card)->item(0);
    
    if (!$link) continue;
    
    preg_match('/id=(\d+)/', $link->getAttribute('href'), $idMatch);
    
    $videos[] = [
        'id' => $idMatch[1] ?? '',
        'title' => $titleEl ? trim($titleEl->textContent) : '',
        'thumbnail' => $img ? $img->getAttribute('src') : '',
        'views' => $viewsEl ? (int)str_replace(',', '', trim(str_replace('•', '', strip_tags($viewsEl->parentNode->textContent ?? '')))) : 0,
        'duration' => $durationEl ? trim(strip_tags($durationEl->textContent)) : '',
        'url' => 'https://videystream.vip/watch.php?id=' . ($idMatch[1] ?? ''),
    ];
}

// Cek next page
$hasNext = $xpath->query('//a[contains(@class, "pagination-btn") and contains(text(), "Next")]')->length > 0;

$data['status'] = true;
$data['result'] = [
    'page' => $page,
    'sort' => $sort,
    'total' => count($videos),
    'has_next' => $hasNext,
    'videos' => $videos,
];

$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);

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