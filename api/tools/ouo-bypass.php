<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - Ouo.io Link Bypasser
// Contoh: {"url": "https://ouo.io/HxFVfD"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url Text Input - URL Ouo.io/Ouo.press yang mau di-bypass

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$url = $_GET['url'] ?? '';
if (empty($url)) {
    die(json_encode(['creator' => 'Tiyanz', 'status' => false, 'message' => 'Gunakan ?url=https://ouo.io/xxx']));
}

// ========== RECAPTCHA V3 SOLVER ==========
function recaptcha_v3() {
    $client = curl_init();
    
    // Step 1: Get anchor token
    $anchor_url = 'https://www.google.com/recaptcha/api2/anchor?ar=1&k=6Lcr1ncUAAAAAH3cghg6cOTPGARa8adOf-y9zv2x&co=aHR0cHM6Ly9vdW8ucHJlc3M6NDQz&hl=en&v=pCoGBhjs9s8EhFOHJFe8cqis&size=invisible&cb=' . time();
    
    curl_setopt_array($client, [
        CURLOPT_URL => $anchor_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => ['Accept: text/html']
    ]);
    $resp = curl_exec($client);
    curl_close($client);
    
    // Extract recaptcha token
    if (!preg_match('/"recaptcha-token" value="([^"]+)"/', $resp, $m)) return '';
    $recaptcha_token = $m[1];
    
    // Step 2: Reload to get answer
    $reload_url = 'https://www.google.com/recaptcha/api2/reload?k=6Lcr1ncUAAAAAH3cghg6cOTPGARa8adOf-y9zv2x';
    $post_data = http_build_query([
        'v' => 'pCoGBhjs9s8EhFOHJFe8cqis',
        'reason' => 'q',
        'c' => $recaptcha_token,
        'k' => '6Lcr1ncUAAAAAH3cghg6cOTPGARa8adOf-y9zv2x',
        'co' => 'aHR0cHM6Ly9vdW8ucHJlc3M6NDQz'
    ]);
    
    $client = curl_init();
    curl_setopt_array($client, [
        CURLOPT_URL => $reload_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $resp = curl_exec($client);
    curl_close($client);
    
    if (preg_match('/"rresp","(.*?)"/', $resp, $m)) return $m[1];
    return '';
}

// ========== OUO BYPASS ==========
function ouo_bypass($url) {
    // Normalize URL
    $temp_url = str_replace('ouo.press', 'ouo.io', $url);
    $parsed = parse_url($temp_url);
    $host = $parsed['host'] ?? 'ouo.io';
    $scheme = $parsed['scheme'] ?? 'https';
    $path = explode('/', trim($parsed['path'] ?? '', '/'));
    $id = end($path);
    
    $cookie_file = sys_get_temp_dir() . '/ouo_' . md5($url) . '.txt';
    
    // Step 1: Visit shortlink
    $client = curl_init();
    curl_setopt_array($client, [
        CURLOPT_URL => $temp_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEFILE => $cookie_file,
        CURLOPT_COOKIEJAR => $cookie_file,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8',
            'Cache-Control: max-age=0',
            'Referer: http://www.google.com/ig/adde?moduleurl=',
            'Upgrade-Insecure-Requests: 1'
        ]
    ]);
    $resp = curl_exec($client);
    $http_code = curl_getinfo($client, CURLINFO_HTTP_CODE);
    $location = curl_getinfo($client, CURLINFO_REDIRECT_URL);
    curl_close($client);
    
    // If direct redirect
    if (!empty($location)) {
        @unlink($cookie_file);
        return $location;
    }
    
    // Step 2: Parse form & submit
    $next_url = "{$scheme}://{$host}/go/{$id}";
    
    for ($attempt = 0; $attempt < 2; $attempt++) {
        if (empty($resp)) break;
        
        // Extract form data
        preg_match_all('/<input[^>]+name="([^"]+)"[^>]+value="([^"]*)"/', $resp, $inputs);
        $data = [];
        foreach ($inputs[1] as $i => $name) {
            $data[$name] = $inputs[2][$i] ?? '';
        }
        
        // Add recaptcha token
        $data['x-token'] = recaptcha_v3();
        
        $client = curl_init();
        curl_setopt_array($client, [
            CURLOPT_URL => $next_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://' . $host,
                'Referer: ' . $temp_url
            ]
        ]);
        $resp = curl_exec($client);
        $location = curl_getinfo($client, CURLINFO_REDIRECT_URL);
        curl_close($client);
        
        if (!empty($location)) {
            @unlink($cookie_file);
            return $location;
        }
        
        $next_url = "{$scheme}://{$host}/xreallcygo/{$id}";
    }
    
    @unlink($cookie_file);
    return null;
}

// ========== MAIN ==========
$result = ouo_bypass($url);

if ($result) {
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => true,
        'input' => ['url' => $url],
        'result' => [
            'original_link' => $url,
            'bypassed_link' => $result
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Gagal bypass Ouo.io'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>