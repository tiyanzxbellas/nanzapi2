<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - REST API ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * 1 ZIP = SEMUA FILE LANGSUNG DIUPLOAD!
 * 
 * Format: JSON API
 * Method: POST
 * Body: { "token": "...", "owner": "...", "repo": "...", "mode": "new|existing", "file": "base64..." }
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '6.0-rest-api'
];

// ========== FUNGSI HELPER ==========

function isBinaryFile($filename) {
    $binaryExtensions = [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.ico', '.webp',
        '.pdf', '.zip', '.rar', '.7z', '.tar', '.gz',
        '.exe', '.dll', '.so', '.dylib', '.bin', '.dat',
        '.mp3', '.mp4', '.avi', '.mov', '.mkv', '.flv',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.woff', '.woff2', '.ttf', '.eot', '.otf',
        '.jar', '.war', '.ear', '.apk', '.ipa',
        '.iso', '.img', '.dmg'
    ];
    $lowerName = strtolower($filename);
    foreach ($binaryExtensions as $ext) {
        if (substr($lowerName, -strlen($ext)) === $ext) {
            return true;
        }
    }
    return false;
}

function bufferToBase64($buffer) {
    return base64_encode($buffer);
}

// ========== GET DEFAULT BRANCH ==========

function getDefaultBranch($token, $owner, $repo) {
    $url = "https://api.github.com/repos/{$owner}/{$repo}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['default_branch'] ?? 'main';
    }
    
    return 'main';
}

// ========== FUNGSI GITHUB API ==========

function uploadToGitHub($token, $owner, $repo, $filePath, $content, $maxRetries = 3) {
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    $attempt = 0;
    while ($attempt < $maxRetries) {
        try {
            // Cek SHA
            $sha = null;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . $token,
                'User-Agent: PHP-GitHub-Uploader'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['sha'])) {
                    $sha = $data['sha'];
                }
            }
            
            // Upload/Update
            $data = [
                'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
                'content' => $content
            ];
            if ($sha) {
                $data['sha'] = $sha;
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . $token,
                'Content-Type: application/json',
                'User-Agent: PHP-GitHub-Uploader'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("CURL Error: {$error}");
            }
            
            if ($httpCode === 200 || $httpCode === 201) {
                return json_decode($response, true);
            }
            
            // Rate limit
            if ($httpCode === 403 || $httpCode === 429) {
                $attempt++;
                sleep(2 * $attempt);
                continue;
            }
            
            throw new Exception("HTTP {$httpCode}");
            
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw new Exception("Gagal upload {$filePath}: " . $e->getMessage());
            }
            sleep(1);
        }
    }
}

function createRepository($token, $owner, $repoName, $isPrivate = false) {
    $url = 'https://api.github.com/user/repos';
    $data = [
        'name' => $repoName,
        'private' => $isPrivate,
        'auto_init' => false
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'Content-Type: application/json',
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("CURL Error: {$error}");
    }
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        $errorMsg = "Gagal membuat repository: {$repoName} (HTTP {$httpCode})";
        if ($response) {
            $respData = json_decode($response, true);
            if (isset($respData['message'])) {
                $errorMsg .= " - " . $respData['message'];
            }
        }
        throw new Exception($errorMsg);
    }
    
    return json_decode($response, true);
}

function checkRepository($token, $owner, $repoName) {
    $url = "https://api.github.com/repos/{$owner}/{$repoName}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// ========== PROSES ZIP ==========

function processZipFile($zipBuffer, $token, $owner, $repo) {
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    // Kumpulkan SEMUA file
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $name = $stat['name'];
        // Skip direktori
        if (!$name || substr($name, -1) === '/') {
            continue;
        }
        $fileList[] = [
            'name' => $name,
            'index' => $i,
            'size' => $stat['size']
        ];
    }
    
    if (empty($fileList)) {
        $zip->close();
        unlink($tempZip);
        throw new Exception('Tidak ada file yang ditemukan dalam ZIP');
    }
    
    $total = count($fileList);
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $failedFiles = [];
    
    // Proses SEMUA file
    foreach ($fileList as $file) {
        try {
            $filePath = $file['name'];
            $content = $zip->getFromIndex($file['index']);
            if ($content === false) {
                throw new Exception('Gagal membaca file');
            }
            
            $base64Content = bufferToBase64($content);
            uploadToGitHub($token, $owner, $repo, $filePath, $base64Content);
            
            $uploaded++;
            $results[] = ['path' => $filePath, 'status' => 'success'];
            
        } catch (Exception $e) {
            $failed++;
            $failedFiles[] = ['path' => $file['name'], 'error' => $e->getMessage()];
            $results[] = ['path' => $file['name'], 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }
    
    $zip->close();
    unlink($tempZip);
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $total,
        'results' => $results,
        'failed_files' => $failedFiles
    ];
}

// ========== HANDLE REQUEST ==========

try {
    // Baca input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input. Please send valid JSON.');
    }
    
    // Validasi parameter
    $required = ['token', 'owner', 'repo', 'mode'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Parameter '{$field}' wajib diisi");
        }
    }
    
    $token = trim($input['token']);
    $owner = trim($input['owner']);
    $repoName = trim($input['repo']);
    $repoType = trim($input['mode']);
    
    // Validasi format token
    if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token) && !preg_match('/^github_pat_[a-zA-Z0-9_]+$/', $token)) {
        throw new Exception('Format token GitHub tidak valid. Harus dimulai dengan ghp_ atau github_pat_');
    }
    
    if ($repoType !== 'new' && $repoType !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'!");
    }
    
    // Cek file ZIP (dari base64)
    if (empty($input['file'])) {
        throw new Exception('File ZIP (base64) wajib diisi');
    }
    
    $zipBuffer = base64_decode($input['file']);
    if ($zipBuffer === false || empty($zipBuffer)) {
        throw new Exception('File ZIP base64 tidak valid atau kosong');
    }
    
    // Validasi ZIP (cek header magic number)
    $zipHeader = substr($zipBuffer, 0, 4);
    if ($zipHeader !== "PK\x03\x04" && $zipHeader !== "PK\x05\x06" && $zipHeader !== "PK\x07\x08") {
        throw new Exception('File bukan ZIP yang valid (signature tidak cocok)');
    }
    
    $fileSize = strlen($zipBuffer);
    $maxSize = 20 * 1024 * 1024; // 20MB
    if ($fileSize > $maxSize) {
        throw new Exception('File ZIP terlalu besar (max 20MB)');
    }
    
    $zipSizeMB = number_format($fileSize / 1024 / 1024, 2);
    
    // ========== PROSES UPLOAD ==========
    
    $repo = $repoName;
    
    // Create atau cek repository
    if ($repoType === 'new') {
        try {
            createRepository($token, $owner, $repoName, false);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                throw new Exception("Repository {$repoName} sudah ada, gunakan mode 'existing'");
            }
            throw $e;
        }
    } else {
        if (!checkRepository($token, $owner, $repoName)) {
            throw new Exception("Repository {$repoName} tidak ditemukan atau token tidak memiliki akses");
        }
    }
    
    // Dapatkan default branch
    $defaultBranch = getDefaultBranch($token, $owner, $repo);
    
    // Proses ZIP
    $result = processZipFile($zipBuffer, $token, $owner, $repo);
    
    // ========== RESPONSE ==========
    
    $response = array_merge($credit, [
        'status' => true,
        'message' => $result['failed'] === 0 ? '✅ Semua file berhasil diupload!' : $result['failed'] . ' file gagal diupload',
        'data' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'default_branch' => $defaultBranch,
            'mode' => $repoType,
            'zip_size_mb' => $zipSizeMB,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'success_rate' => $result['total'] > 0 ? round(($result['uploaded'] / $result['total']) * 100, 2) . '%' : '0%'
        ]
    ]);
    
    // Tambahkan detail jika ada error
    if ($result['failed'] > 0) {
        $response['data']['failed_files'] = $result['failed_files'];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT);
}
?>
