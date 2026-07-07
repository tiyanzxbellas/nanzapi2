<?php
// ============================================================
// Deskripsi: GitHub Upload & Extract - Upload ZIP ke GitHub
// Contoh: github_upload.php?args=ghp_xxxxx|jerexd|my-repo|new&file=/path/to/file.zip
// @param args Format: token|owner|repo|mode
// @param file Path file ZIP yang akan diupload (opsional, bisa via POST)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ============================================================
// AMBIL PARAMETER
// ============================================================
$args = isset($_REQUEST['args']) ? trim($_REQUEST['args']) : '';
$filePath = isset($_REQUEST['file']) ? trim($_REQUEST['file']) : '';

// Kalau ada file upload via POST
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $filePath = $_FILES['file']['tmp_name'];
} elseif (isset($_REQUEST['file']) && file_exists($_REQUEST['file'])) {
    $filePath = $_REQUEST['file'];
}

// ============================================================
// VALIDASI PARAMETER
// ============================================================
if (empty($args)) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => 'Parameter "args" wajib diisi',
        'format' => 'github_upload.php?args=token|owner|repo|mode',
        'example' => 'github_upload.php?args=ghp_xxxxx|jerexd|my-repo|new',
        'note' => 'Reply file ZIP atau kirim via POST dengan field "file"'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$parts = explode('|', $args);
if (count($parts) !== 4) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => 'Format args salah',
        'format' => 'token|owner|repo|mode',
        'example' => 'ghp_xxxxx|jerexd|my-repo|new'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

list($token, $owner, $repoName, $repoType) = array_map('trim', $parts);

if (!in_array($repoType, ['new', 'existing'])) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => 'Mode harus "new" atau "existing"'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================
// CEK FILE
// ============================================================
if (empty($filePath) || !file_exists($filePath)) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => 'File ZIP tidak ditemukan',
        'note' => 'Kirim file ZIP via POST dengan field "file" atau parameter "file=/path/to/file.zip"',
        'file_received' => $filePath
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Cek mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filePath);
finfo_close($finfo);

if ($mime !== 'application/zip' && !str_ends_with($filePath, '.zip')) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => 'File harus berupa ZIP',
        'mime_detected' => $mime
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================
// USER AGENT & IP RANDOM
// ============================================================
$uas = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36'
];

function rUA() { global $uas; return $uas[array_rand($uas)]; }
function rIP() { return rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255); }

// ============================================================
// FUNGSI CURL REQUEST
// ============================================================
function ghRequest($url, $token = null, $method = 'GET', $data = null, $customHeaders = []) {
    $ch = curl_init();
    
    $headers = ['User-Agent: ' . rUA(), 'X-Forwarded-For: ' . rIP()];
    if ($token) $headers[] = "Authorization: token {$token}";
    if ($data) $headers[] = 'Content-Type: application/json';
    if (!empty($customHeaders)) $headers = array_merge($headers, $customHeaders);
    
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $headers
    ];
    
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($data) $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    } elseif ($method === 'PUT') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($data) $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return ['code' => $code, 'response' => $response, 'error' => $error];
}

// ============================================================
// FUNGSI UTAMA
// ============================================================

// 1. Cek file binary
function isBinaryFile($filename) {
    $binaryExtensions = [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.ico', '.webp',
        '.pdf', '.zip', '.rar', '.7z', '.tar', '.gz',
        '.exe', '.dll', '.so', '.dylib', '.bin', '.dat',
        '.mp3', '.mp4', '.avi', '.mov', '.mkv', '.flv',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.woff', '.woff2', '.ttf', '.eot', '.otf'
    ];
    $lowerName = strtolower($filename);
    foreach ($binaryExtensions as $ext) {
        if (str_ends_with($lowerName, $ext)) return true;
    }
    return false;
}

// 2. Upload ke GitHub
function uploadToGitHub($token, $owner, $repo, $filePath, $content) {
    $encodedPath = implode('/', array_map('urlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    // Cek SHA
    $sha = null;
    $result = ghRequest($url, $token, 'GET');
    if ($result['code'] == 200) {
        $data = json_decode($result['response'], true);
        if (isset($data['sha'])) $sha = $data['sha'];
    }
    
    // Upload
    $payload = [
        'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
        'content' => $content
    ];
    if ($sha) $payload['sha'] = $sha;
    
    $result = ghRequest($url, $token, 'PUT', $payload);
    
    if ($result['code'] < 200 || $result['code'] >= 300) {
        throw new Exception("Upload failed: HTTP {$result['code']} - {$result['response']}");
    }
    
    return json_decode($result['response'], true);
}

// 3. Buat repository
function createRepository($token, $owner, $repoName) {
    $url = "https://api.github.com/user/repos";
    $payload = ['name' => $repoName, 'private' => false, 'auto_init' => false];
    
    $result = ghRequest($url, $token, 'POST', $payload);
    
    if ($result['code'] < 200 || $result['code'] >= 300) {
        throw new Exception("Create repo failed: HTTP {$result['code']} - {$result['response']}");
    }
    
    return json_decode($result['response'], true);
}

// 4. Cek repository
function checkRepository($token, $owner, $repoName) {
    $url = "https://api.github.com/repos/{$owner}/{$repoName}";
    $result = ghRequest($url, $token, 'GET');
    return $result['code'] == 200;
}

// 5. Proses ZIP
function processZipFile($zipPath, $token, $owner, $repo, &$progress = []) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new Exception("Cannot open ZIP file");
    }
    
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat['is_dir']) {
            $fileList[] = ['path' => $stat['name'], 'index' => $i];
        }
    }
    
    if (empty($fileList)) {
        $zip->close();
        throw new Exception("Tidak ada file dalam ZIP");
    }
    
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $total = count($fileList);
    $progress = ['total' => $total, 'uploaded' => 0];
    
    foreach ($fileList as $idx => $file) {
        $filePath = $file['path'];
        try {
            $content = $zip->getFromIndex($file['index']);
            if ($content === false) throw new Exception("Cannot read file");
            
            $base64Content = base64_encode($content);
            uploadToGitHub($token, $owner, $repo, $filePath, $base64Content);
            $uploaded++;
            $results[] = ['path' => $filePath, 'status' => 'success'];
            $progress['uploaded'] = $uploaded;
            
            usleep(200000); // 200ms delay
            
        } catch (Exception $err) {
            $failed++;
            $results[] = [
                'path' => $filePath,
                'status' => 'failed',
                'error' => $err->getMessage()
            ];
        }
    }
    
    $zip->close();
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $total,
        'results' => $results
    ];
}

// ============================================================
// EKSEKUSI UTAMA
// ============================================================
try {
    $zipSize = number_format(filesize($filePath) / 1024 / 1024, 2);
    $logs = [];
    $logs[] = "📥 File ZIP diterima: " . basename($filePath) . " ({$zipSize} MB)";
    
    // Cek/Create repository
    if ($repoType === 'new') {
        $logs[] = "📁 Membuat repository baru: {$repoName}...";
        createRepository($token, $owner, $repoName);
        $logs[] = "✅ Repository {$repoName} berhasil dibuat";
        usleep(2000000);
    } else {
        $logs[] = "🔍 Mengecek repository {$repoName}...";
        if (!checkRepository($token, $owner, $repoName)) {
            throw new Exception("Repository {$repoName} tidak ditemukan atau token tidak memiliki akses");
        }
        $logs[] = "✅ Repository ditemukan";
    }
    
    // Proses ZIP
    $logs[] = "📂 Mengekstrak file ZIP...";
    $progress = [];
    $result = processZipFile($filePath, $token, $owner, $repoName, $progress);
    
    // Hapus temp file
    @unlink($filePath);
    
    // Laporan
    $summary = [];
    $summary['total'] = $result['total'];
    $summary['success'] = $result['uploaded'];
    $summary['failed'] = $result['failed'];
    $summary['repository'] = "https://github.com/{$owner}/{$repoName}";
    $summary['progress'] = $progress;
    
    // Ambil failed files
    $failedFiles = array_filter($result['results'], function($r) {
        return $r['status'] === 'failed';
    });
    if (!empty($failedFiles)) {
        $summary['failed_details'] = array_slice(array_values($failedFiles), 0, 10);
    }
    
    // Response
    echo json_encode([
        'status' => true,
        'creator' => 'GitHub Uploader',
        'mode' => $repoType,
        'input' => [
            'token' => substr($token, 0, 10) . '...',
            'owner' => $owner,
            'repo' => $repoName,
            'file' => basename($filePath),
            'size' => $zipSize . ' MB'
        ],
        'result' => $summary,
        'logs' => $logs,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'creator' => 'GitHub Uploader',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>
