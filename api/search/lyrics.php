<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Search Lyrics Lagu
// Contoh: {"q": "bulan madu"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => 'Parameter q wajib diisi'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://www.lyrics.com/lyrics/' . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Nanzz',
        'message' => "Gagal mendapatkan response (HTTP {$http_code})"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Parse HTML dengan DOMDocument
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($response);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Cari semua elemen .sec-lyric.clearfix
$lyricItems = $xpath->query("//div[contains(@class, 'sec-lyric') and contains(@class, 'clearfix')]");

$results = [];

foreach ($lyricItems as $item) {
    // Title
    $titleNode = $xpath->query(".//p[contains(@class, 'lyric-meta-title')]/a", $item);
    $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';
    
    // Artist
    $artistNode = $xpath->query(".//p[contains(@class, 'lyric-meta-artists')]/a | .//p[contains(@class, 'lyric-meta-album-artist')]/a", $item);
    $artist = $artistNode->length > 0 ? trim($artistNode->item(0)->textContent) : '';
    
    // URL Path
    $path = $titleNode->length > 0 ? $titleNode->item(0)->getAttribute('href') : '';
    
    // Lyrics
    $lyricsNode = $xpath->query(".//pre[contains(@class, 'lyric-body')]", $item);
    $lyrics = '';
    if ($lyricsNode->length > 0) {
        $lyrics = trim(preg_replace('/\s+\n/', "\n", $lyricsNode->item(0)->textContent));
    }
    
    if (!empty($title)) {
        $results[] = [
            'title' => $title,
            'artist' => $artist,
            'url' => $path ? 'https://www.lyrics.com' . $path : null,
            'lyrics' => $lyrics
        ];
    }
}

echo json_encode([
    'status' => true,
    'creator' => 'Nanzz',
    'result' => [
        'total' => count($results),
        'data' => $results
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>