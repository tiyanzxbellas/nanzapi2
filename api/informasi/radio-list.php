<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - MyTuner Radio Country List
// Contoh: {"country": "indonesia"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param country (indonesia|united-states|japan|singapore|malaysia|thailand|south-korea|india) Nama Negara

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$country = trim($_GET['country'] ?? 'indonesia');

if (empty($country)) {
    $data = ['status' => false, 'creator' => 'Nanzz', 'input' => ['country' => null], 'result' => ['msg' => 'Parameter country diperlukan']];
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$slug = strtolower(str_replace(' ', '-', $country)) . '-stations';
$url = 'https://mytuner-radio.com/radio/country/' . $slug;

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

$html = grab($url);

if (empty($html)) {
    $data = ['status' => false, 'creator' => 'Nanzz', 'input' => ['country' => $country], 'result' => ['msg' => 'Gagal fetch data']];
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Parse stations
preg_match_all('/"https:\/\/mytuner-radio\.com\/radio\/([^"]+)"/', $html, $matches);

$stations = [];
foreach ($matches[1] as $path) {
    $fullUrl = 'https://mytuner-radio.com/radio/' . $path;
    if (preg_match('/-\d+\/$/', $fullUrl)) {
        // Ambil nama dari URL
        $name = preg_replace('/-(\d+)\/$/', '', $path);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        $name = trim($name);
        
        $stations[] = [
            'name' => $name,
            'url' => $fullUrl,
            'id' => preg_match('/-(\d+)\/$/', $fullUrl, $idMatch) ? $idMatch[1] : null,
        ];
    }
}

// Deduplicate
$stations = array_values(array_unique($stations, SORT_REGULAR));

$data = [
    'status' => true,
    'creator' => 'Nanzz',
    'input' => ['country' => $country],
    'result' => [
        'country' => $country,
        'total' => count($stations),
        'stations' => $stations,
    ]
];

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