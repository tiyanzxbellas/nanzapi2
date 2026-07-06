<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: DaFontStyle Search + Direct Download Link
// Contoh: {"query":"Roboto"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param query Kata Kunci Font

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];
set_time_limit(60);

$query = $_GET['query'] ?? '';

if (!$query) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter query diperlukan']), JSON_PRETTY_PRINT);
    exit;
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return $res;
}

// Search
$html = curlGet('https://dafontstyle.io/?s=' . urlencode($query));
if (!$html) exit(json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal fetch']), JSON_PRETTY_PRINT));

$dom = new DOMDocument(); @$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$fonts = [];
$items = $xpath->query("//li[contains(@class,'entry-list-item')]");

foreach ($items as $item) {
    $titleEl = $xpath->query(".//h2[contains(@class,'entry-title')]//a", $item)->item(0);
    $title = trim($titleEl->textContent ?? '');
    $fontUrl = $titleEl->getAttribute('href') ?? '';
    $imgEl = $xpath->query(".//img[contains(@class,'wp-post-image')]", $item)->item(0);
    $image = $imgEl->getAttribute('src') ?? '';
    $catEl = $xpath->query(".//span[contains(@class,'category-links')]//a", $item)->item(0);
    $category = trim($catEl->textContent ?? '');
    
    if (!$title || !$fontUrl) continue;
    
    // Scrape halaman detail buat dapetin data-zip
    $detailHtml = curlGet($fontUrl);
    $downloadUrl = '';
    
    if ($detailHtml) {
        preg_match('/data-zip="([^"]+)"/', $detailHtml, $m);
        $downloadUrl = $m[1] ?? '';
    }
    
    $fonts[] = [
        'title' => $title,
        'url' => $fontUrl,
        'image' => $image,
        'category' => $category,
        'download_url' => $downloadUrl ?: null
    ];
}

echo json_encode(array_merge($credit, ['status' => true, 'query' => $query, 'total' => count($fonts), 'result' => $fonts]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>