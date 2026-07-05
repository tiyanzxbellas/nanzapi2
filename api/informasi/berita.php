<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Portal Berita Indonesia - Multi Source
// Contoh: {"source":"antara"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param source (antara|cnn|kompas|merdeka|sindonews) Sumber Berita

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(15);

$source = $_GET['source'] ?? 'antara';

$endpoints = [
    'antara' => 'https://api.siputzx.my.id/api/berita/antara',
    'cnn' => 'https://api.siputzx.my.id/api/berita/cnn',
    'kompas' => 'https://api.siputzx.my.id/api/berita/kompas',
    'merdeka' => 'https://api.siputzx.my.id/api/berita/merdeka',
    'sindonews' => 'https://api.siputzx.my.id/api/berita/sindonews',
];

if (!isset($endpoints[$source])) {
    $data = array_merge($credit, ['status' => false, 'message' => 'Sumber tidak valid']);
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$ch = curl_init($endpoints[$source]);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

$data = array_merge($credit, [
    'status' => !empty($result),
    'source' => $source,
    'result' => $result
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