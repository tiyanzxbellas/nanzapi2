<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - UNLIMITED VERCEL ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * base    : https://github.com
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * OPTIMASI UNLIMITED UNTUK VERCEL:
 * - Tanpa batas jumlah file
 * - Auto chunk processing
 * - Resumeable upload (lanjut dari yang gagal)
 * - Partial response jika timeout
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

// ========== DETEKSI ENVIRONMENT ==========
$isVercel = getenv('VERCEL') || getenv('NOW_REGION');
$isServerless = $isVercel || getenv('AWS_LAMBDA_RUNTIME_API');

if ($isServerless) {
    set_time_limit(10);
    ini_set('memory_limit', '256M');
} else {
    set_time_limit(600);
    ini_set('memory_limit', '512M');
    ignore_user_abort(true);
}

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '3.0-unlimited-vercel'
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
        '.js.map', '.css.map'
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

// ========== SESSION MANAGEMENT UNTUK RESUME ==========

function saveProgress($sessionId, $data) {
    $file = sys_get_temp_dir() . "/upload_progress_{$sessionId}.json";
    file_put_contents($file, json_encode($data));
    return $file;
}

function getProgress($sessionId) {
    $file = sys_get_temp_dir() . "/upload_progress_{$sessionId}.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function clearProgress($sessionId) {
    $file = sys_get_temp_dir() . "/upload_progress_{$sessionId}.json";
    if (file_exists($file)) {
        unlink($file);
    }
}

// ========== FUNGSI GITHUB API ==========

function uploadToGitHub($token, $owner, $repo, $filePath, $content, $maxRetries = 2) {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['sha'])) {
                    $sha = $data['sha'];
                }
            }
            
            // Upload
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                return json_decode($response, true);
            }
            
            if ($httpCode === 403) {
                $attempt++;
                if ($attempt < $maxRetries) {
                    usleep(500000 * $attempt);
                }
                continue;
            }
            
            throw new Exception("HTTP {$httpCode}");
            
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw new Exception("Gagal upload {$filePath}: " . $e->getMessage());
            }
            usleep(500000);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// ========== PROSES ZIP UNLIMITED DENGAN CHUNK ==========

function processZipUnlimited($zipBuffer, $token, $owner, $repo, $sessionId = null, $offset = 0) {
    global $isServerless;
    
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    // Kumpulkan semua file (TANPA BATAS)
    $fileList = [];
    for ($i = $offset; $i < $zip->numFiles; $i++) {
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
        return [
            'uploaded' => 0,
            'failed' => 0,
            'total' => 0,
            'completed' => true,
            'results' => []
        ];
    }
    
    $total = count($fileList);
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $processed = 0;
    
    // Chunk processing: proses per 2 file untuk Vercel
    $chunkSize = $isServerless ? 2 : 5;
    $startTime = time();
    $maxExecutionTime = $isServerless ? 8 : 580;
    
    for ($i = 0; $i < $total; $i += $chunkSize) {
        // Cek timeout
        if ($isServerless && (time() - $startTime) > $maxExecutionTime) {
            // Simpan progress untuk resume
            if ($sessionId) {
                $progressData = [
                    'offset' => $offset + $i,
                    'uploaded' => $uploaded,
                    'failed' => $failed,
                    'results' => $results,
                    'total_files' => $zip->numFiles
                ];
                saveProgress($sessionId, $progressData);
            }
            
            $zip->close();
            unlink($tempZip);
            
            return [
                'uploaded' => $uploaded,
                'failed' => $failed,
                'total' => $zip->numFiles,
                'processed' => $processed + $uploaded + $failed,
                'results' => $results,
                'completed' => false,
                'partial' => true,
                'session_id' => $sessionId,
                'message' => "Timeout, silahkan lanjutkan dengan session_id: {$sessionId}",
                'next_offset' => $offset + $i
            ];
        }
        
        $chunk = array_slice($fileList, $i, $chunkSize);
        
        foreach ($chunk as $file) {
            try {
                $filePath = $file['name'];
                $content = $zip->getFromIndex($file['index']);
                if ($content === false) {
                    throw new Exception('Gagal membaca file');
                }
                
                $base64Content = bufferToBase64($content);
                uploadToGitHub($token, $owner, $repo, $filePath, $base64Content, 2);
                
                $uploaded++;
                $results[] = ['path' => $filePath, 'status' => 'success'];
                
            } catch (Exception $e) {
                $failed++;
                $results[] = ['path' => $file['name'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
            
            $processed++;
        }
        
        // Delay minimal
        if ($i + $chunkSize < $total) {
            usleep($isServerless ? 30000 : 100000);
        }
    }
    
    $zip->close();
    unlink($tempZip);
    
    // Hapus session jika selesai
    if ($sessionId) {
        clearProgress($sessionId);
    }
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $zip->numFiles,
        'processed' => $processed,
        'results' => $results,
        'completed' => true,
        'partial' => false
    ];
}

// ========== HANDLE REQUEST ==========

try {
    // Meta response
    $responseMeta = [
        'environment' => $isServerless ? 'Vercel' : 'Standard',
        'timeout_limit' => $isServerless ? '10 seconds' : '600 seconds',
        'max_files' => 'UNLIMITED'
    ];
    
    // Cek apakah ini request resume
    $isResume = isset($_POST['session_id']) && !empty($_POST['session_id']);
    
    // Validasi parameter
    if (!isset($_POST['token']) || !isset($_POST['owner']) || !isset($_POST['repo'])) {
        throw new Exception(
            "Parameter wajib:\n" .
            "token - GitHub Personal Access Token (repo scope)\n" .
            "owner - Username GitHub\n" .
            "repo - Nama repository\n" .
            "mode - new (buat baru) atau existing (pakai yg ada)\n" .
            "session_id - (optional) untuk resume upload\n\n" .
            "Contoh: token=ghp_xxxxx&owner=jerexd&repo=my-repo&mode=new"
        );
    }
    
    $token = trim($_POST['token']);
    $owner = trim($_POST['owner']);
    $repoName = trim($_POST['repo']);
    $repoType = isset($_POST['mode']) ? trim($_POST['mode']) : 'existing';
    
    // Validasi token format
    if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token)) {
        throw new Exception('Format token GitHub tidak valid. Harus dimulai dengan ghp_');
    }
    
    if ($repoType !== 'new' && $repoType !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'!");
    }
    
    // Handle resume
    if ($isResume) {
        $sessionId = trim($_POST['session_id']);
        $progress = getProgress($sessionId);
        
        if (!$progress) {
            throw new Exception("Session ID tidak valid atau sudah expired");
        }
        
        // Resume proses
        $result = processZipUnlimited(
            file_get_contents($_FILES['file']['tmp_name']),
            $token,
            $owner,
            $repoName,
            $sessionId,
            $progress['offset']
        );
        
        // Merge hasil sebelumnya
        $result['results'] = array_merge($progress['results'], $result['results']);
        $result['uploaded'] = $progress['uploaded'] + $result['uploaded'];
        $result['failed'] = $progress['failed'] + $result['failed'];
        $result['total'] = $progress['total_files'];
        
        echo json_encode([
            'creator' => 'Tiyanz',
            'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
            'version' => '3.0-unlimited-vercel',
            'status' => true,
            'environment' => $responseMeta,
            'type' => 'resume',
            'result' => [
                'repository' => "https://github.com/{$owner}/{$repoName}",
                'total_files' => $result['total'],
                'uploaded' => $result['uploaded'],
                'failed' => $result['failed'],
                'success_rate' => $result['total'] > 0 ? round(($result['uploaded'] / $result['total']) * 100, 2) . '%' : '0%',
                'completed' => $result['completed'],
                'session_id' => $sessionId
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Cek file ZIP
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
    
    // Batasi ukuran (tetap 20MB)
    $maxSize = 20 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception('File ZIP terlalu besar (max 20MB)');
    }
    
    $zipBuffer = file_get_contents($fileTmp);
    $zipSizeMB = number_format($fileSize / 1024 / 1024, 2);
    
    // ========== PROSES UPLOAD ==========
    
    $repo = $repoName;
    $startProcessTime = microtime(true);
    
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
    
    // Generate session ID untuk resume
    $sessionId = uniqid('upload_', true);
    
    // Proses ZIP unlimited
    $result = processZipUnlimited($zipBuffer, $token, $owner, $repo, $sessionId, 0);
    
    $processTime = round(microtime(true) - $startProcessTime, 2);
    
    // ========== RESPONSE ==========
    
    $response = [
        'creator' => 'Tiyanz',
        'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
        'version' => '3.0-unlimited-vercel',
        'status' => true,
        'environment' => $responseMeta,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'zip_size_mb' => $zipSizeMB,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'success_rate' => $result['total'] > 0 ? round(($result['uploaded'] / $result['total']) * 100, 2) . '%' : '0%',
            'process_time' => $processTime . 's',
            'completed' => $result['completed']
        ]
    ];
    
    // Jika partial (timeout), berikan session_id untuk resume
    if (isset($result['partial']) && $result['partial']) {
        $response['result']['partial'] = true;
        $response['result']['session_id'] = $sessionId;
        $response['result']['next_offset'] = $result['next_offset'];
        $response['result']['message'] = $result['message'];
        $response['result']['resume_instruction'] = [
            'method' => 'POST',
            'params' => [
                'token' => $token,
                'owner' => $owner,
                'repo' => $repo,
                'mode' => $repoType,
                'session_id' => $sessionId,
                'file' => 'upload ZIP yang sama'
            ]
        ];
    }
    
    if ($result['failed'] > 0) {
        $failedFiles = array_slice(
            array_filter($result['results'], function($r) { return $r['status'] === 'failed'; }),
            0,
            5
        );
        $response['result']['failed_details'] = array_map(function($f) {
            return $f['path'] . ' - ' . $f['error'];
        }, $failedFiles);
        if ($result['failed'] > 5) {
            $response['result']['failed_details'][] = "dan " . ($result['failed'] - 5) . " file lainnya";
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code($isServerless ? 408 : 500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'environment' => $isServerless ? 'Vercel' : 'Standard'
    ]), JSON_PRETTY_PRINT);
}
?>
