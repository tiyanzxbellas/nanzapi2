<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Website Copier to ZIP (saveweb2zip.com)
// Contoh: {"url": "https://www.yorunime.sbs"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
set_time_limit(300);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$url = $_GET['url'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter url wajib diisi (URL valid)'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('BASE', 'https://copier.saveweb2zip.com');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36');

function curlPost($url, $body) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: ' . UA
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: ' . UA]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

try {
    // Start copy
    $copyResult = curlPost(BASE . '/api/copySite', [
        'url' => $url,
        'renameAssets' => false,
        'saveStructure' => true,
        'alternativeAlgorithm' => true,
        'mobileVersion' => true
    ]);
    
    if (empty($copyResult['md5'])) throw new Exception('Gagal memulai copy');
    
    $hash = $copyResult['md5'];
    
    // Polling
    $maxAttempts = 120;
    $result = null;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        sleep(2);
        
        $status = curlGet(BASE . '/api/getStatus/' . $hash);
        
        if ($status && ($status['isFinished'] ?? false)) {
            $result = [
                'status' => $status['success'] ?? false,
                'md5' => $hash,
                'copied_files' => $status['copiedFilesAmount'] ?? 0,
                'download_url' => BASE . '/api/downloadArchive/' . $hash
            ];
            break;
        }
    }
    
    if (!$result) throw new Exception('Timeout');
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => $result
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>