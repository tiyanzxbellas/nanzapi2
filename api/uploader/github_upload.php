<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: GitHub Upload & Extractor (ZIP to Repository)
// Contoh: {"token": "ghp_xxxxx", "owner": "username", "repo": "repo-name", "mode": "new"}

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];

// ========== HELPER FUNCTIONS ==========

function delay($ms) {
    usleep($ms * 1000);
}

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
        if (substr($lowerName, -strlen($ext)) === $ext) {
            return true;
        }
    }
    return false;
}

// ========== GITHUB FUNCTIONS ==========

function uploadToGitHub($token, $owner, $repo, $filePath, $content) {
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    $sha = null;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['sha'])) {
            $sha = $data['sha'];
        }
    }
    
    $data = [
        'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
        'content' => base64_encode($content)
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("HTTP {$httpCode}");
    }
    
    return true;
}

function createRepository($token, $owner, $repoName) {
    $url = 'https://api.github.com/user/repos';
    $data = [
        'name' => $repoName,
        'private' => false,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        if (strpos($response, 'already exists') !== false) {
            throw new Exception("Repository sudah ada");
        }
        throw new Exception("Gagal create repository: HTTP {$httpCode}");
    }
    
    return true;
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// ========== PROSES ZIP ==========

function extractAndUpload($zipPath, $token, $owner, $repo) {
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath) !== true) {
        throw new Exception('Gagal membuka file ZIP');
    }
    
    $totalFiles = $zip->numFiles;
    $uploaded = 0;
    $failed = 0;
    $failedFiles = [];
    $totalSize = 0;
    
    for ($i = 0; $i < $totalFiles; $i++) {
        $stat = $zip->statIndex($i);
        $fileName = $stat['name'];
        $fileSize = $stat['size'];
        
        if (substr($fileName, -1) === '/') {
            continue;
        }
        
        $totalSize += $fileSize;
        
        try {
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                throw new Exception('Gagal baca file');
            }
            
            uploadToGitHub($token, $owner, $repo, $fileName, $content);
            $uploaded++;
            
        } catch (Exception $e) {
            $failed++;
            $failedFiles[] = $fileName . ' - ' . $e->getMessage();
        }
        
        delay(200);
    }
    
    $zip->close();
    
    return [
        'total' => $totalFiles,
        'uploaded' => $uploaded,
        'failed' => $failed,
        'failed_files' => $failedFiles,
        'total_size' => $totalSize
    ];
}

// ========== HANDLE REQUEST ==========

try {
    if (!isset($_POST['token']) || !isset($_POST['owner']) || !isset($_POST['repo'])) {
        throw new Exception('Parameter wajib: token, owner, repo');
    }
    
    $token = trim($_POST['token']);
    $owner = trim($_POST['owner']);
    $repo = trim($_POST['repo']);
    $mode = trim($_POST['mode'] ?? 'existing');
    
    if ($mode !== 'new' && $mode !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'");
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File ZIP wajib diupload');
    }
    
    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    
    $fileMime = mime_content_type($fileTmp);
    if (!strpos($fileMime, 'zip') && !strpos($fileName, '.zip')) {
        throw new Exception('File harus berformat ZIP');
    }
    
    if ($mode === 'new') {
        createRepository($token, $owner, $repo);
        delay(2000);
    } else {
        if (!checkRepository($token, $owner, $repo)) {
            throw new Exception("Repository {$repo} tidak ditemukan atau token tidak memiliki akses");
        }
    }
    
    $result = extractAndUpload($fileTmp, $token, $owner, $repo);
    
    $response = array_merge($credit, [
        'status' => true,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'total_size' => number_format($result['total_size'] / 1024 / 1024, 2) . ' MB'
        ]
    ]);
    
    if ($result['failed'] > 0) {
        $failedList = array_slice($result['failed_files'], 0, 5);
        $response['result']['failed_files'] = $failedList;
        if ($result['failed'] > 5) {
            $response['result']['failed_files'][] = "dan " . ($result['failed'] - 5) . " file lainnya";
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]));
}
?>
