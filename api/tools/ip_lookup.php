<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: IP Address Checker (IP Geolocation Lookup)
// Contoh: {"ip": "8.8.8.8"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param ip Text Input - IP Address yang ingin dicek (kosongkan untuk cek IP sendiri)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

// ========== KODE UTAMA ==========
$ip = isset($_GET['ip']) ? trim($_GET['ip']) : '';

// Validasi format IP (jika diisi)
if (!empty($ip)) {
    $parts = explode('.', $ip);
    $valid = true;
    if (count($parts) !== 4) $valid = false;
    foreach ($parts as $p) {
        if (!ctype_digit($p) || intval($p) < 0 || intval($p) > 255) {
            $valid = false;
            break;
        }
    }
    if (!$valid) {
        echo json_encode([
            'status' => false,
            'creator' => 'RebornLookUp',
            'message' => 'Invalid IP address format. Example: ?ip=8.8.8.8',
            'query_time' => date('Y-m-d\TH:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========== API WRAPPER: ip-api.com ==========
$api_url = !empty($ip) 
    ? "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query"
    : "http://ip-api.com/json/?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9,id;q=0.8',
        'Referer: https://api-rebornlookup.com/'
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Error handling
if ($curl_error || $response === false) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => "Connection error: " . ($curl_error ? $curl_error : 'Unknown error'),
        'http_code' => $http_code,
        'query_time' => date('Y-m-d\TH:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$api_data = json_decode($response, true);

if (!$api_data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => 'Error parsing API response',
        'http_code' => $http_code,
        'query_time' => date('Y-m-d\TH:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($api_data['status']) && $api_data['status'] === 'fail') {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => $api_data['message'] ?? 'Failed to get IP information',
        'http_code' => $http_code,
        'query_time' => date('Y-m-d\TH:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== SUSUN RESPONSE ==========
$data = [
    'status' => true,
    'creator' => 'Tiyanz',
    'input' => ['ip' => !empty($ip) ? $ip : 'auto-detect'],
    'result' => [
        'ip' => $api_data['query'] ?? 'N/A',
        'country' => $api_data['country'] ?? 'N/A',
        'country_code' => $api_data['countryCode'] ?? 'N/A',
        'region' => $api_data['region'] ?? 'N/A',
        'region_name' => $api_data['regionName'] ?? 'N/A',
        'city' => $api_data['city'] ?? 'N/A',
        'zip' => $api_data['zip'] ?? 'N/A',
        'lat' => $api_data['lat'] ?? 0,
        'lon' => $api_data['lon'] ?? 0,
        'timezone' => $api_data['timezone'] ?? 'N/A',
        'isp' => $api_data['isp'] ?? 'N/A',
        'org' => $api_data['org'] ?? 'N/A',
        'as' => $api_data['as'] ?? 'N/A',
        'maps_link' => isset($api_data['lat'], $api_data['lon']) 
            ? "https://www.google.com/maps?q={$api_data['lat']},{$api_data['lon']}" 
            : 'N/A',
        'query_time' => date('Y-m-d\TH:i:s')
    ]
];

// Cleanup keys
$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);
$data['creator'] = 'Tiyanz';

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ========== HELPER FUNCTION ==========
function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>