<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Multi-Platform Media Downloader (TikTok + Instagram + YouTube + X + Spotify + Pinterest)
// Contoh: {"url": "https://vt.tiktok.com/ZSQCLU6Le/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL Video/Musik (TikTok/Instagram/YouTube/X/Spotify/Pinterest)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Parameter "url" wajib diisi']]);
    exit;
}

$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
$result = [];

// ========== TIKTOK ==========
if (preg_match('/tiktok\.com/', $url)) {
    $result['platform'] = 'tiktok';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://tiktokio.com/api/v1/tk/html',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['vid' => $url, 'prefix' => 'tiktokio.com']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://tiktokio.com', 'Referer: https://tiktokio.com/']
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    
    $result['thumbnail'] = $xpath->evaluate('string(//div[contains(@class, "video-info")]//img/@src)');
    $result['title'] = trim($xpath->evaluate('string(//div[contains(@class, "video-info")]//h3)'));
    
    $linkNodes = $xpath->query('//div[contains(@class, "download-links")]//a');
    $media = [];
    foreach ($linkNodes as $link) {
        $class = $link->getAttribute('class');
        $type = strpos($class, 'blue') !== false ? 'no_watermark' : 
                (strpos($class, 'green') !== false ? 'no_watermark_hd' : 
                (strpos($class, 'gray') !== false ? 'watermark' : 
                (strpos($class, 'purple') !== false ? 'mp3' : 'unknown')));
        $media[] = ['type' => $type, 'label' => trim($link->textContent), 'url' => $link->getAttribute('href')];
    }
    $result['media'] = $media;
}
// ========== INSTAGRAM ==========
elseif (preg_match('/instagram\.com/', $url)) {
    $result['platform'] = 'instagram';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://fastvidl.com/api/lookup',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['url' => $url]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://fastvidl.com', 'Referer: https://fastvidl.com/']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (!$data || !$data['ok']) { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal mengambil data Instagram']]); exit; }
    
    $result['thumbnail'] = $data['thumbnail'] ?? '';
    $result['title'] = $data['title'] ?? '';
    $result['username'] = $data['username'] ?? '';
    $media = [];
    foreach ($data['media'] as $item) { $media[] = ['type' => $item['type'], 'quality' => $item['quality'], 'url' => $item['url']]; }
    $result['media'] = $media;
}
// ========== YOUTUBE ==========
elseif (preg_match('/youtu\.be|youtube\.com/', $url)) {
    $result['platform'] = 'youtube';
    
    preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/', $url, $m) || preg_match('/watch\?v=([A-Za-z0-9_-]+)/', $url, $m) || preg_match('/\/embed\/([A-Za-z0-9_-]+)/', $url, $m) || preg_match('/\/shorts\/([A-Za-z0-9_-]+)/', $url, $m);
    $vid = $m[1] ?? '';
    if (empty($vid)) { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal ekstrak ID']]); exit; }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.vidssave.com/api/contentsite_api/media/parse',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['auth' => '20250901majwlqo', 'domain' => 'api-ak.vidssave.com', 'origin' => 'source', 'link' => "https://youtu.be/{$vid}"]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']
    ]);
    $response = curl_exec($ch); curl_close($ch);
    $data = json_decode($response, true);
    if (!$data || $data['status'] != 1) { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal YouTube']]); exit; }
    
    $d = $data['data'];
    $result['title'] = $d['title'] ?? ''; $result['thumbnail'] = $d['thumbnail'] ?? ''; $result['duration'] = gmdate('i:s', $d['duration'] ?? 0); $result['author'] = $d['user_item']['nickname'] ?? '';
    $media = [];
    if (isset($d['media'])) { foreach ($d['media'] as $m) { foreach ($m['resources'] as $r) { $media[] = ['type' => $m['type'], 'quality' => $r['quality'], 'format' => $r['format'], 'url' => $r['download_url'] ?? '']; } } }
    $result['media'] = $media;
}
// ========== X/TWITTER ==========
elseif (preg_match('/x\.com|twitter\.com/', $url)) {
    $result['platform'] = 'x';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://snapx.to/api/ajaxSearch',
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['q' => $url, 'lang' => 'en']),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: */*', 'X-Requested-With: XMLHttpRequest', 'Origin: https://snapx.to', 'Referer: https://snapx.to/']
    ]);
    $response = curl_exec($ch); curl_close($ch);
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'ok') { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal X/Twitter']]); exit; }
    
    $dom = new DOMDocument(); libxml_use_internal_errors(true); @$dom->loadHTML(mb_convert_encoding($data['data'], 'HTML-ENTITIES', 'UTF-8')); libxml_clear_errors(); $xpath = new DOMXPath($dom);
    $result['thumbnail'] = $xpath->evaluate('string(//div[contains(@class, "thumbnail")]//img/@src)');
    $result['title'] = trim($xpath->evaluate('string(//h3)'));
    
    $linkNodes = $xpath->query('//a[contains(@class, "tw-button-dl")]'); $media = [];
    foreach ($linkNodes as $link) {
        $href = $link->getAttribute('href'); $label = trim($link->textContent); $class = $link->getAttribute('class');
        if (!empty($href) && $href !== '#') { preg_match('/\((\d+p)\)/', $label, $qm); $media[] = ['type' => 'video', 'quality' => $qm[1] ?? '', 'label' => $label, 'url' => $href]; }
    }
    $result['media'] = $media;
}
// ========== SPOTIFY ==========
elseif (preg_match('/spotify\.com/', $url)) {
    $result['platform'] = 'spotify';
    
    $ch = curl_init('https://musicfab.io/api/spotify');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['url' => $url]), CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => $ua, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://musicfab.io', 'Referer: https://musicfab.io/']]);
    $response = curl_exec($ch); curl_close($ch);
    $data = json_decode($response, true); $m = $data['data']['metadata'] ?? null;
    if (!$m || empty($m['download'])) { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal Spotify']]); exit; }
    $result['title'] = $m['name'] ?? ''; $result['artist'] = $m['artist'] ?? ''; $result['image'] = $m['image'] ?? '';
    $result['media'] = [['type' => 'mp3', 'url' => $m['download']]];
}
// ========== PINTEREST ==========
elseif (preg_match('/pin\.it|pinterest\.com/', $url)) {
    $result['platform'] = 'pinterest';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://sortpins.com/api/pinterest-download',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['url' => $url]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://sortpins.com', 'Referer: https://sortpins.com/']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (!$data) { echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Gagal Pinterest']]); exit; }
    
    $result['title'] = $data['title'] ?? '';
    $result['description'] = $data['description'] ?? '';
    $result['thumbnail'] = $data['imageUrl'] ?? '';
    
    $media = [];
    if (!empty($data['videoUrl'])) {
        $media[] = ['type' => 'video', 'url' => $data['videoUrl']];
    }
    if (!empty($data['imageUrl'])) {
        $media[] = ['type' => 'image', 'url' => $data['imageUrl']];
    }
    $result['media'] = $media;
}
else {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Platform tidak didukung']]);
    exit;
}

echo json_encode(['status' => true, 'creator' => 'Nanzz', 'input' => ['url' => $url], 'result' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>