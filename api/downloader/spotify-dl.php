<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Spotify Downloader via MusicFab
// Contoh: {"url":"https://open.spotify.com/track/4bCoqCjwZggHxHcUSRpFDG"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL Spotify

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$url = $_GET['url'] ?? '';

if (!$url) {
    $data = array_merge($credit, ['status' => false, 'message' => 'Parameter url diperlukan']);
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$ch = curl_init('https://musicfab.io/api/spotify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['url' => $url]),
    CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36',
        'Accept: */*', 'Content-Type: application/json',
        'Origin: https://musicfab.io', 'Referer: https://musicfab.io/',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
    ]
]);
$res = curl_exec($ch); curl_close($ch);
$result = json_decode($res, true);
$metadata = $result['data']['metadata'] ?? null;

if (!$metadata || empty($metadata['download'])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal download']), JSON_PRETTY_PRINT);
    exit;
}

$data = array_merge($credit, [
    'status' => true,
    'input' => $url,
    'download_url' => $metadata['download'],
    'metadata' => [
        'name' => $metadata['name'] ?? null,
        'artist' => $metadata['artist'] ?? null,
        'album' => $metadata['album'] ?? null,
        'duration' => $metadata['duration'] ?? null,
        'image' => $metadata['image'] ?? null
    ]
]);

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