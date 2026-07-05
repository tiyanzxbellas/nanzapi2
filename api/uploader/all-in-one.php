<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Multi Uploader (Gobox + Filegoat + Uguu + Upload.ee)
// Contoh: POST multipart/form-data dengan field "file"
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// POST file: File yang ingin diupload

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'File wajib diupload via POST multipart/form-data dengan field "file"']);
    exit;
}

$file = $_FILES['file'];
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

// ========== GOBOX ==========
function uploadGobox($file, $ua) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.gobox.my.id/upload',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Origin: https://www.gobox.my.id', 'Referer: https://www.gobox.my.id/']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $url = is_array($data) ? ($data['url'] ?? $data['file'] ?? $data['link'] ?? '') : trim(strval($data));
    return ['platform' => 'gobox', 'url' => $url ?: $response, 'raw' => $data];
}

// ========== FILEGOAT ==========
function uploadFilegoat($file, $ua) {
    $BASE = 'https://filego.at';
    $S3 = 'https://filegoat.s3.de.io.cloud.ovh.net';
    
    // Upload file
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$BASE/api/file/upload",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Origin: ' . $BASE, 'Referer: ' . $BASE . '/']
    ]);
    $uRes = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $ids = array_filter($uRes['fileIds'] ?? $uRes['ids'] ?? [$uRes['id'] ?? null]);
    if (empty($ids)) return ['platform' => 'filegoat', 'error' => 'Upload gagal'];
    
    // Buat bucket
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$BASE/api/bucket",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['fileIds' => $ids, 'deleteTime' => 7, 'extendOnView' => false, 'clientId' => bin2hex(random_bytes(16))]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    $bRes = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (empty($bRes['slug'])) return ['platform' => 'filegoat', 'error' => 'Bucket gagal'];
    $slug = $bRes['slug'];
    
    // Ambil info bucket
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$BASE/api/bucket/$slug",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $dRes = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $files = array_map(function($f) use ($S3) {
        return [
            'name' => $f['fileName'] ?? $f['file_name'] ?? '',
            'size' => $f['bytes'] ?? 0,
            'direct' => "$S3/{$f['savedName']}/{$f['fileName']}",
            'download' => "$S3/{$f['savedName']}/{$f['fileName']}?download=true"
        ];
    }, $dRes['files'] ?? []);
    
    return ['platform' => 'filegoat', 'slug' => $slug, 'url' => "$BASE/bucket/$slug", 'files' => $files];
}

// ========== UGUU.SE ==========
function uploadUguu($file, $ua) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://uguu.se/upload.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['files[]' => new CURLFile($file['tmp_name'], 'application/octet-stream', $file['name'])],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Origin: https://uguu.se', 'Referer: https://uguu.se/']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $url = $data['files'][0]['url'] ?? null;
    return ['platform' => 'uguu', 'url' => $url, 'success' => ($data['success'] ?? false) && $url];
}

// ========== UPLOAD.EE ==========
function uploadUploadEe($file, $ua) {
    $BASE = 'https://www.upload.ee';
    $isImage = strpos($file['type'], 'image/') === 0;
    
    // Get upload ID
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $BASE . '/ubr_link_upload.php?rnd_id=' . time(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Referer: ' . $BASE . '/']
    ]);
    $upPage = curl_exec($ch);
    curl_close($ch);
    
    preg_match('/startUpload\("([^"]+)"/', $upPage, $idMatch);
    if (empty($idMatch[1])) return ['platform' => 'upload.ee', 'error' => 'Upload ID tidak ditemukan'];
    $uploadId = $idMatch[1];
    
    // Upload file
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $BASE . '/cgi-bin/ubr_upload.pl?X-Progress-ID=' . $uploadId . '&upload_id=' . $uploadId,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'upfile_0' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'link' => '', 'email' => '', 'category' => $isImage ? 'cat_picture' : 'cat_file',
            'big_resize' => 'none', 'small_resize' => '120x90'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ['Origin: ' . $BASE, 'Referer: ' . $BASE . '/?']
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // Get result
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $BASE . '/?page=finished&upload_id=' . $uploadId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => $ua
    ]);
    $resPage = curl_exec($ch);
    curl_close($ch);
    
    preg_match('/id=["\']file_src["\'][^>]*value=["\']([^"\']+)["\']/i', $resPage, $srcMatch);
    preg_match('/View file:\s*<br\s*\/?>\s*<a href=["\']?([^"\'>\s]+)["\']?/i', $resPage, $viewMatch);
    $viewUrl = $srcMatch[1] ?? $viewMatch[1] ?? null;
    
    if (!$viewUrl) return ['platform' => 'upload.ee', 'error' => 'View URL tidak ditemukan'];
    if ($isImage) $viewUrl = str_replace('/files/', '/image/', $viewUrl);
    
    return ['platform' => 'upload.ee', 'url' => $viewUrl, 'type' => $isImage ? 'image' : 'file'];
}

// ========== EXECUTE ALL ==========
$results = [
    'gobox' => uploadGobox($file, $ua),
    'filegoat' => uploadFilegoat($file, $ua),
    'uguu' => uploadUguu($file, $ua),
    'upload.ee' => uploadUploadEe($file, $ua)
];

echo json_encode([
    'status' => true,
    'creator' => 'Nanzz',
    'input_name' => $file['name'],
    'results' => $results
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>