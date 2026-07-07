<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR ]
 * Converted from JavaScript to PHP
 * Original source: https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Converted from JS to PHP',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '1.0'
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

function delay($ms) {
    usleep($ms * 1000);
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
                delay(2000 * $attempt);
                continue;
            }
            
            throw new Exception("HTTP {$httpCode}");
            
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw new Exception("Gagal upload {$filePath}: " . $e->getMessage());
            }
            delay(1000);
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

// ========== PROSES ZIP ==========

function processZipFile($zipBuffer, $token, $owner, $repo) {
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    // Kumpulkan semua file (kecuali folder)
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        // Skip folder
        if (substr($stat['name'], -1) === '/') {
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
    
    // Proses setiap file
    foreach ($fileList as $index => $file) {
        try {
            $filePath = $file['name'];
            $content = $zip->getFromIndex($file['index']);
            if ($content === false) {
                throw new Exception('Gagal membaca file');
            }
            
            // Cek apakah binary
            $isBinary = isBinaryFile($filePath);
            $base64Content = bufferToBase64($content);
            
            uploadToGitHub($token, $owner, $repo, $filePath, $base64Content);
            
            $uploaded++;
            $results[] = ['path' => $filePath, 'status' => 'success'];
            
            // Progress setiap 5 file
            if (($index + 1) % 5 === 0 || $index === $total - 1) {
                // Log progress (opsional)
                error_log("Progress: {$uploaded}/{$total} file diupload...");
            }
            
            delay(200); // Delay 200ms antara upload
            
        } catch (Exception $e) {
            $failed++;
            $results[] = ['path' => $file['name'], 'status' => 'failed', 'error' => $e->getMessage()];
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
    
    // Validasi file ZIP
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
        delay(2000); // Delay 2 detik setelah create repo
    } else {
        if (!checkRepository($token, $owner, $repoName)) {
            throw new Exception("Repository {$repoName} tidak ditemukan atau token tidak memiliki akses");
        }
    }
    
    // Proses ZIP
    $result = processZipFile($zipBuffer, $token, $owner, $repo);
    
    // ========== RESPONSE ==========
    
    $response = array_merge($credit, [
        'status' => true,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'mode' => $repoType,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'success_rate' => $result['total'] > 0 ? round(($result['uploaded'] / $result['total']) * 100, 2) . '%' : '0%',
            'details' => $result['results']
        ]
    ]);
    
    if ($result['failed'] > 0) {
        $failedFiles = array_slice(array_filter($result['results'], function($r) {
            return $r['status'] === 'failed';
        }), 0, 5);
        
        $response['warnings'] = $result['failed'] . ' file gagal diupload';
        $response['failed_files'] = array_map(function($f) {
            return $f['path'] . ' (' . ($f['error'] ?? 'Unknown error') . ')';
        }, $failedFiles);
        
        if ($result['failed'] > 5) {
            $response['failed_files'][] = "dan " . ($result['failed'] - 5) . " file lainnya";
        }
    } else {
        $response['message'] = '✅ Semua file berhasil diupload!';
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
