<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Myinstants Search Sounds
// Contoh: {"q": "vine boom", "page": "1"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param q Text Input - Kata kunci pencarian suara (contoh: vine boom, bruh, anime wow, fart)
// @param page Text Input - Halaman hasil pencarian (default: 1)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$q = $_GET['q'] ?? '';
$page = intval($_GET['page'] ?? 1);

if (empty($q)) {
    die(json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gunakan ?q=vine+boom',
        'example' => '?q=vine+boom&page=1'
    ]));
}

$url = 'https://www.myinstants.com/en/search/?name=' . urlencode($q) . '&page=' . $page;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);
$html = curl_exec($ch); curl_close($ch);

if (!$html) {
    die(json_encode(['creator' => 'Nanzz', 'status' => false, 'message' => 'Gagal scrape']));
}

$sounds = [];
preg_match_all('/<div class="instant">.*?onclick="play\(\'([^\']+)\'.*?<a href="([^"]+)" class="instant-link[^"]*">([^<]+)<\/a>/s', $html, $m, PREG_SET_ORDER);

foreach ($m as $item) {
    $mp3_url = $item[1];
    if (strpos($mp3_url, 'http') !== 0) {
        $mp3_url = 'https://www.myinstants.com' . $mp3_url;
    }
    
    $sounds[] = [
        'title' => trim($item[3]),
        'url' => 'https://www.myinstants.com' . $item[2],
        'mp3' => $mp3_url
    ];
}

echo json_encode([
    'creator' => 'Nanzz',
    'status' => true,
    'input' => [
        'q' => $q,
        'page' => $page
    ],
    'result' => [
        'keyword' => $q,
        'page' => $page,
        'count' => count($sounds),
        'sounds' => $sounds
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>