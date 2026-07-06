<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - MyTuner Radio Stream Extractor
// Contoh: {"url": "https://mytuner-radio.com/radio/prambors-1022-fm-432/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL Stasiun Radio MyTuner

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$credit = ['creator' => 'Tiyanz'];
set_time_limit(30);

$url = trim($_GET['url'] ?? '');

if (empty($url)) {
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'result' => ['msg' => 'Parameter url diperlukan']]);
    exit;
}

function grab($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return $res;
}

function buildKey($str) {
    $out = ''; $j = 0; $len = strlen($str);
    for ($i = 0; $i < 32; $i++) {
        $out .= dechex(ord($str[$j]));
        if (++$j >= $len) $j = 0;
    }
    return $out;
}

function crack($ivHex, $cipherB64, $ts) {
    $key = @hex2bin(buildKey($ts));
    $iv = @hex2bin($ivHex);
    $ct = @base64_decode($cipherB64);
    if (!$key || !$iv || !$ct) return null;
    
    $dec = @openssl_decrypt($ct, 'aes-256-cfb', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
    if ($dec === false) return null;
    
    $cut = strcspn($dec, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0b\x0c\x0d\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f");
    return trim(substr($dec, 0, $cut));
}

$html = grab($url);
if (empty($html)) {
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'result' => ['msg' => 'Gagal fetch halaman']]);
    exit;
}

preg_match('/data-timestamp="(\d+)"/', $html, $tsHit);
$ts = $tsHit[1] ?? null;

preg_match('/formatPlaylist\(\s*(\[[\s\S]*?\])\s*\)/', $html, $plHit);
$streams = [];

if (!empty($plHit[1]) && $ts) {
    $rows = json_decode($plHit[1], true);
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (!empty($row['cipher']) && !empty($row['iv'])) {
                $streamUrl = crack($row['iv'], $row['cipher'], $ts);
                if ($streamUrl && strpos($streamUrl, 'http') === 0) {
                    $streams[] = ['url' => $streamUrl, 'type' => $row['type'] ?? 'mp3'];
                }
            }
        }
    }
}

// Info stasiun
preg_match('/<title>([^<]+)<\/title>/', $html, $titleHit);
$name = $titleHit[1] ?? '';
$name = str_replace([' live', ' | MyTuner Radio', ' radio station'], '', $name);
$name = trim($name);

echo json_encode([
    'status' => !empty($streams),
    'creator' => 'Tiyanz',
    'input' => ['url' => $url],
    'result' => [
        'name' => $name,
        'total_streams' => count($streams),
        'streams' => $streams,
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>