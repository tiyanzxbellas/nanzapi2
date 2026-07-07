<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - OPTIMIZED ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * base    : https://github.com
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * Optimasi: Timeout handling, chunk processing, error recovery
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');
set_time_limit(600); // 10 menit timeout
ini_set('memory_limit', '512M');
ignore_user_abort(true);

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '2.0-optimized'
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

// ========== FUNGSI GITHUB API DENGAN RETRY ==========

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
            
            // Rate limit handling
            if ($httpCode === 403) {
                $attempt++;
                sleep(2 * $attempt); // Exponential backoff
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

// ========== PROSES ZIP DENGAN CHUNK ==========

function processZipFile($zipBuffer, $token, $owner, $repo, &$progress = null) {
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    // Batasi jumlah file
    $maxFiles = 100;
    if ($zip->numFiles > $maxFiles) {
        $zip->close();
        unlink($tempZip);
        throw new Exception("ZIP memiliki {$zip->numFiles} file, melebihi batas {$maxFiles} file");
    }
    
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
    
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $total = count($fileList);
    $progress = ['current' => 0, 'total' => $total];
    
    // Batch processing - upload per 5 file
    $batchSize = 5;
    for ($i = 0; $i < $total; $i += $batchSize) {
        $batch = array_slice($fileList, $i, $batchSize);
        
        foreach ($batch as $file) {
            try {
                $filePath = $file['name'];
                $isBinary = isBinaryFile($filePath);
                
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
            
            $progress['current'] = $uploaded + $failed;
        }
        
        // Rate limit delay - lebih pendek
        if ($i + $batchSize < $total) {
            usleep(100000); // 100ms
        }
    }
    
    $zip->close();
    unlink($tempZip);
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $total,
        'results' => $results
    ];
}

// ========== PROSES ALTERNATIF - EKSTRAK DULU ==========

function processZipByExtract($zipBuffer, $token, $owner, $repo, &$progress = null) {
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    $extractPath = sys_get_temp_dir() . '/extract_' . uniqid();
    mkdir($extractPath, 0777, true);
    
    $zip = new ZipArchive();
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        rmdir($extractPath);
        throw new Exception('Gagal membuka ZIP');
    }
    
    // Ekstrak semua file
    $zip->extractTo($extractPath);
    $zip->close();
    unlink($tempZip);
    
    // Scan files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $total = iterator_count($files);
    $progress = ['current' => 0, 'total' => $total];
    
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        
        try {
            $relativePath = substr($file->getPathname(), strlen($extractPath) + 1);
            $content = file_get_contents($file->getPathname());
            $base64Content = bufferToBase64($content);
            
            uploadToGitHub($token, $owner, $repo, $relativePath, $base64Content);
            
            $uploaded++;
            $results[] = ['path' => $relativePath, 'status' => 'success'];
            
        } catch (Exception $e) {
            $failed++;
            $results[] = ['path' => $relativePath, 'status' => 'failed', 'error' => $e->getMessage()];
        }
        
        $progress['current'] = $uploaded + $failed;
        usleep(50000); // 50ms delay
    }
    
    // Cleanup
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::CHILD_FIRST)
    ) as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($extractPath);
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => $total,
        'results' => $results
    ];
}

// ========== HANDLE REQUEST ==========

try {
    // Validasi parameter
    if (!isset($_POST['token']) || !isset($_POST['owner']) || !isset($_POST['repo']) || !isset($_POST['mode'])) {
        throw new Exception(
            "Parameter wajib:\n" .
            "token - GitHub Personal Access Token (repo scope)\n" .
            "owner - Username GitHub\n" .
            "repo - Nama repository\n" .
            "mode - new (buat baru) atau existing (pakai yg ada)\n\n" .
            "Contoh: token=ghp_xxxxx&owner=jerexd&repo=my-repo&mode=new"
        );
    }
    
    $token = trim($_POST['token']);
    $owner = trim($_POST['owner']);
    $repoName = trim($_POST['repo']);
    $repoType = trim($_POST['mode']);
    
    if ($repoType !== 'new' && $repoType !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'!");
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
    
    // Batasi ukuran
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
    
    // Pilih metode proses
    $useExtractMethod = true; // Ubah ke false untuk metode langsung dari ZIP
    
    if ($useExtractMethod) {
        $result = processZipByExtract($zipBuffer, $token, $owner, $repo, $progress);
    } else {
        $result = processZipFile($zipBuffer, $token, $owner, $repo, $progress);
    }
    
    // ========== RESPONSE ==========
    
    $response = [
        'creator' => 'Tiyanz',
        'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
        'version' => '2.0',
        'status' => true,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'zip_size_mb' => $zipSizeMB,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'success_rate' => round(($result['uploaded'] / $result['total']) * 100, 2) . '%'
        ]
    ];
    
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
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]), JSON_PRETTY_PRINT);
}
?>
