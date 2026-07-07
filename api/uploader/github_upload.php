<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - OPTIMIZED FOR VERCEL ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * base    : https://github.com
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * Optimasi: Timeout handling, chunk processing, error recovery
 * Versi Vercel: Dengan batasan timeout 10 detik
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

// ========== DETEKSI ENVIRONMENT ==========
$isVercel = getenv('VERCEL') || getenv('NOW_REGION');
$isServerless = $isVercel || getenv('AWS_LAMBDA_RUNTIME_API');

if ($isServerless) {
    // Vercel free tier: 10 detik timeout
    set_time_limit(10);
    ini_set('memory_limit', '256M');
    // Vercel tidak support ignore_user_abort
} else {
    set_time_limit(600);
    ini_set('memory_limit', '512M');
    ignore_user_abort(true);
}

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '2.1-vercel'
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

// ========== FUNGSI GITHUB API DENGAN TIMEOUT OPTIMAL ==========

function uploadToGitHub($token, $owner, $repo, $filePath, $content, $maxRetries = 2) {
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    $attempt = 0;
    while ($attempt < $maxRetries) {
        try {
            // Cek SHA dengan timeout pendek
            $sha = null;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . $token,
                'User-Agent: PHP-GitHub-Uploader'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 detik untuk GET
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['sha'])) {
                    $sha = $data['sha'];
                }
            }
            
            // Upload/Update dengan timeout 10 detik
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 detik untuk PUT
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                return json_decode($response, true);
            }
            
            // Rate limit handling
            if ($httpCode === 403) {
                $attempt++;
                if ($attempt < $maxRetries) {
                    usleep(500000 * $attempt); // 0.5s, 1s backoff
                }
                continue;
            }
            
            throw new Exception("HTTP {$httpCode}");
            
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw new Exception("Gagal upload {$filePath}: " . $e->getMessage());
            }
            usleep(500000); // 0.5s delay
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

// ========== PROSES ZIP OPTIMAL UNTUK VERCEL ==========

function processZipFileVercel($zipBuffer, $token, $owner, $repo, &$progress = null) {
    global $isServerless;
    
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    // Batasi jumlah file untuk Vercel (max 20 file)
    $maxFiles = $isServerless ? 20 : 100;
    if ($zip->numFiles > $maxFiles) {
        $zip->close();
        unlink($tempZip);
        throw new Exception("ZIP memiliki {$zip->numFiles} file, melebihi batas Vercel {$maxFiles} file");
    }
    
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat['size'] && substr($stat['name'], -1) === '/') {
            continue;
        }
        // Skip file terlalu besar (>2MB) di Vercel
        if ($isServerless && $stat['size'] > 2 * 1024 * 1024) {
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
        throw new Exception('Tidak ada file yang valid ditemukan dalam ZIP');
    }
    
    $uploaded = 0;
    $failed = 0;
    $skipped = 0;
    $results = [];
    $total = count($fileList);
    $progress = ['current' => 0, 'total' => $total];
    
    // Batch lebih kecil untuk Vercel
    $batchSize = $isServerless ? 3 : 5;
    $startTime = time();
    $maxExecutionTime = $isServerless ? 8 : 580; // 8 detik untuk Vercel
    
    for ($i = 0; $i < $total; $i += $batchSize) {
        // Cek timeout untuk Vercel
        if ($isServerless && (time() - $startTime) > $maxExecutionTime) {
            $zip->close();
            unlink($tempZip);
            throw new Exception("Timeout Vercel: hanya {$uploaded} file dari {$total} yang terupload");
        }
        
        $batch = array_slice($fileList, $i, $batchSize);
        
        foreach ($batch as $file) {
            try {
                $filePath = $file['name'];
                
                // Skip file binary besar
                if ($isBinaryFile($filePath) && $file['size'] > 1 * 1024 * 1024) {
                    $skipped++;
                    $results[] = ['path' => $filePath, 'status' => 'skipped', 'reason' => 'Binary file too large'];
                    continue;
                }
                
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
            
            $progress['current'] = $uploaded + $failed + $skipped;
        }
        
        // Delay minimal untuk Vercel
        if ($i + $batchSize < $total) {
            usleep(50000); // 50ms
        }
    }
    
    $zip->close();
    unlink($tempZip);
    
    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'skipped' => $skipped,
        'total' => $total + $skipped,
        'results' => $results,
        'is_vercel' => $isServerless
    ];
}

// ========== PROSES ALTERNATIF - EKSTRAK DULU (OPTIMAL) ==========

function processZipByExtractVercel($zipBuffer, $token, $owner, $repo, &$progress = null) {
    global $isServerless;
    
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
    
    // Scan files dengan batasan
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $uploaded = 0;
    $failed = 0;
    $skipped = 0;
    $results = [];
    $total = 0;
    $fileList = [];
    
    // Kumpulkan file dengan batasan
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        
        // Skip file > 2MB di Vercel
        if ($isServerless && $file->getSize() > 2 * 1024 * 1024) {
            $skipped++;
            $results[] = ['path' => $file->getFilename(), 'status' => 'skipped', 'reason' => 'File too large'];
            continue;
        }
        
        $fileList[] = $file;
        $total++;
        
        // Batasi total file untuk Vercel
        if ($isServerless && $total >= 20) {
            break;
        }
    }
    
    $progress = ['current' => 0, 'total' => $total];
    $startTime = time();
    $maxExecutionTime = $isServerless ? 8 : 580;
    
    foreach ($fileList as $file) {
        // Cek timeout
        if ($isServerless && (time() - $startTime) > $maxExecutionTime) {
            throw new Exception("Timeout Vercel: hanya {$uploaded} file dari {$total} yang terupload");
        }
        
        try {
            $relativePath = substr($file->getPathname(), strlen($extractPath) + 1);
            $content = file_get_contents($file->getPathname());
            $base64Content = bufferToBase64($content);
            
            uploadToGitHub($token, $owner, $repo, $relativePath, $base64Content, 2);
            
            $uploaded++;
            $results[] = ['path' => $relativePath, 'status' => 'success'];
            
        } catch (Exception $e) {
            $failed++;
            $results[] = ['path' => $relativePath, 'status' => 'failed', 'error' => $e->getMessage()];
        }
        
        $progress['current'] = $uploaded + $failed + $skipped;
        usleep(30000); // 30ms delay
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
        'skipped' => $skipped,
        'total' => $total + $skipped,
        'results' => $results,
        'is_vercel' => $isServerless
    ];
}

// ========== HANDLE REQUEST ==========

try {
    // Deteksi environment di response
    $responseMeta = [
        'environment' => $isServerless ? 'Vercel' : 'Standard',
        'timeout_limit' => $isServerless ? '10 seconds' : '600 seconds'
    ];
    
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
    
    // Validasi token format
    if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token)) {
        throw new Exception('Format token GitHub tidak valid. Harus dimulai dengan ghp_');
    }
    
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
    
    // Batasi ukuran untuk Vercel (max 10MB)
    $maxSize = $isServerless ? 10 * 1024 * 1024 : 20 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception('File ZIP terlalu besar (max ' . ($isServerless ? '10MB' : '20MB') . ')');
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
    
    // Pilih metode proses - selalu gunakan extract method untuk Vercel
    $useExtractMethod = true;
    
    if ($useExtractMethod) {
        $result = processZipByExtractVercel($zipBuffer, $token, $owner, $repo, $progress);
    } else {
        $result = processZipFileVercel($zipBuffer, $token, $owner, $repo, $progress);
    }
    
    $processTime = round(microtime(true) - $startProcessTime, 2);
    
    // ========== RESPONSE ==========
    
    $response = [
        'creator' => 'Tiyanz',
        'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
        'version' => '2.1-vercel',
        'status' => true,
        'environment' => $responseMeta,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'zip_size_mb' => $zipSizeMB,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'skipped' => isset($result['skipped']) ? $result['skipped'] : 0,
            'success_rate' => $result['total'] > 0 ? round(($result['uploaded'] / $result['total']) * 100, 2) . '%' : '0%',
            'process_time' => $processTime . 's',
            'is_vercel_optimized' => $isServerless
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
    
    if (isset($result['skipped']) && $result['skipped'] > 0) {
        $response['result']['note'] = "{$result['skipped']} file di-skip karena terlalu besar atau binary untuk Vercel";
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code($isServerless ? 408 : 500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'environment' => $isServerless ? 'Vercel' : 'Standard',
        'suggestion' => $isServerless ? 'Coba kurangi jumlah file di ZIP atau gunakan file yang lebih kecil' : null
    ]), JSON_PRETTY_PRINT);
}
?>
