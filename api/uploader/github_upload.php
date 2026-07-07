<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - 1 ZIP ALL FILES ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * 1 ZIP = SEMUA FILE LANGSUNG DIUPLOAD!
 * Support: Background processing, status check, unlimited files
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '4.0-one-shot-bg'
];

// ========== FUNGSI HELPER ==========

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

function bufferToBase64($buffer) {
    return base64_encode($buffer);
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
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                return json_decode($response, true);
            }
            
            if ($httpCode === 403) {
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
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("Gagal membuat repository: HTTP {$httpCode}");
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

// ========== PROSES ZIP 1 SHOT ==========

function processZipOneShot($zipBuffer, $token, $owner, $repo, $sessionId) {
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
        if (!$stat['size'] && substr($stat['name'], -1) === '/') {
            continue;
        }
        $fileList[] = [
            'name' => $stat['name'],
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
    $statusFile = sys_get_temp_dir() . "/upload_status_{$sessionId}.json";
    
    // Simpan status awal
    $statusData = [
        'session_id' => $sessionId,
        'total_files' => $total,
        'uploaded' => 0,
        'failed' => 0,
        'status' => 'processing',
        'start_time' => time(),
        'results' => []
    ];
    file_put_contents($statusFile, json_encode($statusData));
    
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
            $results[] = ['path' => $file['name'], 'status' => 'failed', 'error' => $e->getMessage()];
        }
        
        // Update status setiap 5 file
        if (($uploaded + $failed) % 5 == 0 || ($uploaded + $failed) == $total) {
            $statusData['uploaded'] = $uploaded;
            $statusData['failed'] = $failed;
            $statusData['results'] = $results;
            file_put_contents($statusFile, json_encode($statusData));
        }
    }
    
    $zip->close();
    unlink($tempZip);
    
    // Update status selesai
    $statusData['uploaded'] = $uploaded;
    $statusData['failed'] = $failed;
    $statusData['status'] = 'completed';
    $statusData['results'] = $results;
    $statusData['end_time'] = time();
    file_put_contents($statusFile, json_encode($statusData));
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $total,
        'results' => $results,
        'session_id' => $sessionId
    ];
}

// ========== HANDLE REQUEST ==========

try {
    // ========== CEK STATUS ==========
    if (isset($_POST['check']) && !empty($_POST['check'])) {
        $sessionId = trim($_POST['check']);
        $statusFile = sys_get_temp_dir() . "/upload_status_{$sessionId}.json";
        
        if (!file_exists($statusFile)) {
            throw new Exception('Session ID tidak ditemukan atau sudah expired');
        }
        
        $status = json_decode(file_get_contents($statusFile), true);
        
        echo json_encode(array_merge($credit, [
            'status' => true,
            'type' => 'check_status',
            'result' => [
                'session_id' => $sessionId,
                'total_files' => $status['total_files'],
                'uploaded' => $status['uploaded'],
                'failed' => $status['failed'],
                'progress' => round(($status['uploaded'] / $status['total_files']) * 100, 2) . '%',
                'status' => $status['status'],
                'is_done' => $status['status'] === 'completed',
                'results' => $status['results']
            ]
        ]));
        exit;
    }

    // ========== VALIDASI PARAMETER ==========
    if (!isset($_POST['token']) || !isset($_POST['owner']) || !isset($_POST['repo']) || !isset($_POST['mode'])) {
        throw new Exception(
            "Parameter wajib:\n" .
            "token - GitHub Personal Access Token (repo scope)\n" .
            "owner - Username GitHub\n" .
            "repo - Nama repository\n" .
            "mode - new (buat baru) atau existing (pakai yg ada)\n" .
            "check - (optional) session_id untuk cek status\n\n" .
            "Contoh: token=ghp_xxxxx&owner=jerexd&repo=my-repo&mode=new"
        );
    }
    
    $token = trim($_POST['token']);
    $owner = trim($_POST['owner']);
    $repoName = trim($_POST['repo']);
    $repoType = trim($_POST['mode']);
    
    if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token)) {
        throw new Exception('Format token GitHub tidak valid. Harus dimulai dengan ghp_');
    }
    
    if ($repoType !== 'new' && $repoType !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'!");
    }
    
    // ========== CEK FILE ZIP ==========
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File ZIP wajib diupload');
    }
    
    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    
    // Validasi file
    $fileMime = mime_content_type($fileTmp);
    if (!strpos($fileMime, 'zip') && !strpos($fileName, '.zip')) {
        throw new Exception('File harus berformat ZIP');
    }
    
    $maxSize = 20 * 1024 * 1024; // 20MB
    if ($fileSize > $maxSize) {
        throw new Exception('File ZIP terlalu besar (max 20MB)');
    }
    
    $zipBuffer = file_get_contents($fileTmp);
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
    
    // Generate session ID
    $sessionId = uniqid('upload_', true);
    
    // ========== JALANKAN BACKGROUND ==========
    // Pake exec biar jalan di background
    $cmd = "php -r \"include '" . __FILE__ . "'; processBackground('{$sessionId}');\" > /dev/null 2>&1 &";
    
    // Simpan data untuk background process
    $jobData = [
        'zip_buffer' => base64_encode($zipBuffer),
        'token' => $token,
        'owner' => $owner,
        'repo' => $repo,
        'session_id' => $sessionId
    ];
    $jobFile = sys_get_temp_dir() . "/job_{$sessionId}.json";
    file_put_contents($jobFile, json_encode($jobData));
    
    exec($cmd);
    
    // ========== RESPONSE LANGSUNG ==========
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'type' => 'background_processing',
        'message' => '📦 Upload sedang diproses di background!',
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'zip_size_mb' => $zipSizeMB,
            'session_id' => $sessionId,
            'how_to_check' => [
                'method' => 'POST',
                'params' => [
                    'check' => $sessionId,
                    'token' => $token,
                    'owner' => $owner,
                    'repo' => $repo,
                    'mode' => $repoType
                ]
            ],
            'note' => '⚠️ Proses berjalan di background, cek status dengan parameter check'
        ]
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]));
}

// ========== BACKGROUND PROCESSOR ==========

function processBackground($sessionId) {
    $jobFile = sys_get_temp_dir() . "/job_{$sessionId}.json";
    
    if (!file_exists($jobFile)) {
        return;
    }
    
    $jobData = json_decode(file_get_contents($jobFile), true);
    $zipBuffer = base64_decode($jobData['zip_buffer']);
    $token = $jobData['token'];
    $owner = $jobData['owner'];
    $repo = $jobData['repo'];
    
    try {
        $result = processZipOneShot($zipBuffer, $token, $owner, $repo, $sessionId);
        unlink($jobFile);
    } catch (Exception $e) {
        $statusFile = sys_get_temp_dir() . "/upload_status_{$sessionId}.json";
        file_put_contents($statusFile, json_encode([
            'session_id' => $sessionId,
            'status' => 'error',
            'error' => $e->getMessage()
        ]));
        unlink($jobFile);
    }
}

// Jalankan background jika dipanggil
if (isset($_POST['background_process']) && $_POST['background_process'] == '1') {
    $sessionId = $_POST['job_id'];
    processBackground($sessionId);
    exit;
}
?>
