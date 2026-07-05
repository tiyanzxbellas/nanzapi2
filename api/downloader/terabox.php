<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Terabox Downloader
// Contoh: {"url": "https://1024terabox.com/s/1Ey2P0-j21zdoET65g0YYug"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$url = $_GET['url'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Parameter url wajib diisi (URL valid)']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('ORIGIN', 'https://1024teradownloader.com');
define('UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0');

try {
    // ========== GET COOKIES ==========
    $ch = curl_init(ORIGIN . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
    ]);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    curl_close($ch);
    
    // Parse cookies
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
    $cookies = implode('; ', $matches[1] ?? []);
    
    if (empty($cookies)) {
        throw new Exception('No session cookies issued');
    }
    
    // ========== API REQUEST ==========
    $formBody = 'url=' . urlencode($url);
    
    $ch = curl_init(ORIGIN . '/api/stream');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $formBody);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: */*',
        'Origin: ' . ORIGIN,
        'Referer: ' . ORIGIN . '/',
        'Cookie: ' . $cookies,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $apiResponse = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($apiResponse)) {
        throw new Exception('API stream returned HTTP ' . $http_code);
    }
    
    $data = json_decode($apiResponse, true);
    
    if (!($data['status'] ?? '') === 'success') {
        throw new Exception($data['message'] ?? 'Terabox API error');
    }
    
    // Format files
    $files = [];
    foreach ($data['list'] ?? [] as $f) {
        $files[] = [
            'id' => $f['fs_id'] ?? '',
            'name' => $f['name'] ?? '',
            'path' => $f['file_path'] ?? '',
            'type' => $f['type'] ?? '',
            'isFolder' => ($f['is_dir'] ?? '') === '1',
            'size' => $f['size'] ?? 0,
            'sizeFormatted' => $f['size_formatted'] ?? '',
            'downloadUrl' => $f['normal_dlink'] ?? '',
            'folder' => $f['folder'] ?? ''
        ];
    }
    
    echo json_encode(array_merge(
        $credit,
        [
            'status' => true,
            'result' => [
                'sourceUrl' => $url,
                'totalFiles' => $data['total_files'] ?? 0,
                'totalFolders' => $data['total_folders'] ?? 0,
                'files' => $files
            ]
        ]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => $e->getMessage()]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>