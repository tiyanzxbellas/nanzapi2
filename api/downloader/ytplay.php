<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - YouTube Search + Audio Download
// Contoh: {"q": "multo"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param q Text Input - Kata kunci pencarian atau URL YouTube

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$q = $_GET['q'] ?? '';
if (empty($q)) {
    die(json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => 'Gunakan ?q= untuk kata kunci/URL']));
}

$ua = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36';
$video_id = '';

// Auto deteksi URL YouTube
if (preg_match('/(?:youtu\.be\/|v\/|vi\/|u\/\w\/|embed\/|shorts\/|watch\?v=|&v=)([A-Za-z0-9_-]{11})/', $q, $m)) {
    $video_id = $m[1];
} else {
    // Search pakai API Varhad (terbukti WORKING)
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
    
    if ($http_code === 200 && $resp) {
        $search_data = json_decode($resp, true);
        if ($search_data && isset($search_data['result'][0]['link'])) {
            preg_match('/watch\?v=([A-Za-z0-9_-]{11})/', $search_data['result'][0]['link'], $m);
            $video_id = $m[1] ?? '';
        }
    }
}

if (empty($video_id)) {
    die(json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => 'Video tidak ditemukan']));
}

// Download via ht.flvto.online
$dl_body = json_encode(['id' => $video_id, 'fileType' => 'mp3']);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://ht.flvto.online/converter',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $dl_body,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Origin: https://ht.flvto.online',
        'Referer: https://ht.flvto.online/'
    ]
]);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Cek response flvto
$dl_data = json_decode($resp, true);
$download_url = '';

if ($dl_data && in_array($dl_data['status'] ?? '', ['ok', 'success'])) {
    $download_url = $dl_data['link'] ?? $dl_data['formats'][0]['url'] ?? '';
}

// Fallback ke Vidssave
if (empty($download_url)) {
    $post_data = http_build_query([
        'auth' => '20250901majwlqo',
        'domain' => 'api-ak.vidssave.com',
        'origin' => 'source',
        'link' => "https://youtu.be/{$video_id}"
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.vidssave.com/api/contentsite_api/media/parse',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: https://api.vidssave.com'
        ]
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($resp, true);
    if ($data && isset($data['data'])) {
        $best_br = 0;
        $map = ['320KBPS' => 320, '256KBPS' => 256, '192KBPS' => 192, '128KBPS' => 128, '96KBPS' => 96, '64KBPS' => 64, '48KBPS' => 48];
        
        $resources = $data['data']['resources'] ?? [];
        foreach ($data['data']['media'] ?? [] as $m) {
            $resources = array_merge($resources, $m['resources'] ?? []);
        }
        
        foreach ($resources as $r) {
            if (($r['type'] ?? '') === 'audio' && !empty($r['download_url'])) {
                $br = $map[strtoupper($r['quality'] ?? '')] ?? 0;
                if ($br > $best_br) { $best_br = $br; $download_url = $r['download_url']; }
            }
        }
    }
}

if (empty($download_url)) {
    die(json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Gagal mendapatkan URL download. Coba lagi nanti.',
        'debug' => [
            'flvto_response' => $dl_data,
            'video_id' => $video_id
        ]
    ]));
}

// Output JSON dengan download URL
echo json_encode([
    'creator' => 'Tiyanz',
    'status' => true,
    'input' => ['q' => $q, 'video_id' => $video_id],
    'result' => [
        'videoId' => $video_id,
        'title' => $dl_data['title'] ?? $data['data']['title'] ?? 'Unknown',
        'youtube_url' => "https://youtu.be/{$video_id}",
        'download_url' => $download_url
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>