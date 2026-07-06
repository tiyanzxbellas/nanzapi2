<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - TikTok Downloader via TikTokIO
// Contoh: {"url": "https://vt.tiktok.com/ZSQCLU6Le/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL Video TikTok (vt.tiktok.com / tiktok.com / vm.tiktok.com)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'result' => ['error' => 'Parameter "url" wajib diisi']]);
    exit;
}

// Validasi URL TikTok
if (!preg_match('/tiktok\.com/', $url)) {
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'result' => ['error' => 'URL harus dari TikTok (tiktok.com)']]);
    exit;
}

$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

// Request ke TikTokIO API
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://tiktokio.com/api/v1/tk/html',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'vid' => $url,
        'prefix' => 'tiktokio.com'
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: text/html,application/json,*/*',
        'Origin: https://tiktokio.com',
        'Referer: https://tiktokio.com/'
    ]
]);

$html = curl_exec($ch);
curl_close($ch);

if (empty($html)) {
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'result' => ['error' => 'Gagal mengambil data dari TikTokIO']]);
    exit;
}

// Parse HTML response
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Thumbnail
$imgNodes = $xpath->query('//div[contains(@class, "video-info")]//img');
$thumbnail = $imgNodes->length > 0 ? $imgNodes->item(0)->getAttribute('src') : '';

// Title
$titleNodes = $xpath->query('//div[contains(@class, "video-info")]//h3');
$title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';

// Download links
$linkNodes = $xpath->query('//div[contains(@class, "download-links")]//a');
$downloads = [];
foreach ($linkNodes as $link) {
    $href = $link->getAttribute('href');
    $class = $link->getAttribute('class');
    $text = trim($link->textContent);
    
    $type = 'unknown';
    if (strpos($class, 'blue') !== false) $type = 'watermark_removed';
    elseif (strpos($class, 'green') !== false) $type = 'watermark_removed_hd';
    elseif (strpos($class, 'gray') !== false) $type = 'watermark';
    elseif (strpos($class, 'purple') !== false) $type = 'mp3';
    
    $downloads[] = [
        'type' => $type,
        'label' => $text,
        'url' => $href
    ];
}

echo json_encode([
    'status' => true,
    'creator' => 'Tiyanz',
    'input' => ['url' => $url],
    'result' => [
        'thumbnail' => $thumbnail,
        'title' => $title,
        'downloads' => $downloads
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>