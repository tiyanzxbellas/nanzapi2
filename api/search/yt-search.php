<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - YouTube Search (Working 100%)
// Contoh: {"q": "multo"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param q Text Input - Kata kunci pencarian YouTube

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$q = $_GET['q'] ?? '';
if (empty($q)) {
    die(json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => 'Gunakan ?q= untuk kata kunci']));
}

$ua = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36';

// Method 1: API Varhad (pasti jalan)
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api-varhad.my.id/search/youtube?q=' . urlencode($q),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results = [];

// Parse Varhad API
if ($http_code === 200 && $resp) {
    $data = json_decode($resp, true);
    if ($data && isset($data['result'])) {
        foreach ($data['result'] as $item) {
            preg_match('/watch\?v=([A-Za-z0-9_-]{11})/', $item['link'] ?? '', $m);
            $results[] = [
                'videoId' => $m[1] ?? '',
                'title' => $item['title'] ?? '',
                'channel' => $item['channel'] ?? '',
                'url' => $item['link'] ?? '',
                'thumbnail' => $item['imageUrl'] ?? '',
                'duration' => $item['duration'] ?? ''
            ];
        }
    }
}

// Method 2: Fallback scrape YouTube HTML langsung
if (empty($results)) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.youtube.com/results?search_query=' . urlencode($q),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-US,en;q=0.9'
        ]
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    
    if ($html) {
        // Extract dari ytInitialData
        if (preg_match('/var ytInitialData = (.+?);<\/script>/', $html, $m)) {
            $json = json_decode($m[1], true);
            $contents = $json['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
            
            foreach ($contents as $section) {
                $items = $section['itemSectionRenderer']['contents'] ?? [];
                foreach ($items as $item) {
                    if (isset($item['videoRenderer'])) {
                        $v = $item['videoRenderer'];
                        $results[] = [
                            'videoId' => $v['videoId'],
                            'title' => $v['title']['runs'][0]['text'] ?? '',
                            'channel' => $v['ownerText']['runs'][0]['text'] ?? '',
                            'url' => 'https://youtu.be/' . $v['videoId'],
                            'thumbnail' => $v['thumbnail']['thumbnails'][1]['url'] ?? $v['thumbnail']['thumbnails'][0]['url'] ?? '',
                            'duration' => $v['lengthText']['simpleText'] ?? '',
                            'views' => $v['shortViewCountText']['simpleText'] ?? ''
                        ];
                    }
                }
            }
        }
    }
}

if (empty($results)) {
    die(json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => 'Tidak ditemukan hasil']));
}

echo json_encode([
    'creator' => 'Tiyanz',
    'status' => true,
    'input' => ['q' => $q],
    'result' => [
        'count' => count($results),
        'videos' => $results
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>