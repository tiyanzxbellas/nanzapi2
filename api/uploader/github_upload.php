<?php

/**
 * GitHub Upload & Extract Plugin
 * Upload file ZIP ke GitHub dan ekstrak otomatis
 * 
 * Cara penggunaan:
 * github_upload <token>|<owner>|<repo>|<mode>
 * 
 * Parameter:
 * - token: GitHub Personal Access Token (repo scope)
 * - owner: Username GitHub
 * - repo: Nama repository
 * - mode: new (buat baru) atau existing (pakai yg ada)
 * 
 * Contoh:
 * github_upload ghp_xxxxx|jerexd|my-repo|new
 */

// Konfigurasi
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Fungsi untuk delay
function delay($ms) {
    usleep($ms * 1000);
}

// Cek apakah file binary berdasarkan ekstensi
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
        if (str_ends_with($lowerName, $ext)) {
            return true;
        }
    }
    return false;
}

// Buffer ke Base64
function bufferToBase64($buffer) {
    return base64_encode($buffer);
}

// Upload file ke GitHub
function uploadToGitHub($token, $owner, $repo, $filePath, $content, $isBase64 = true) {
    $encodedPath = implode('/', array_map('urlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    // Cek apakah file sudah ada
    $sha = null;
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: token {$token}",
            "User-Agent: PHP-GitHub-Uploader"
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (isset($data['sha'])) {
                $sha = $data['sha'];
            }
        }
    } catch (Exception $e) {
        // File tidak ditemukan, akan dibuat baru
    }
    
    // Siapkan data untuk upload
    $payload = [
        'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
        'content' => $content
    ];
    
    if ($sha) {
        $payload['sha'] = $sha;
    }
    
    // Upload file
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token {$token}",
        "Content-Type: application/json",
        "User-Agent: PHP-GitHub-Uploader"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Upload failed: HTTP {$httpCode}");
    }
    
    return json_decode($response, true);
}

// Buat repository baru
function createRepository($token, $owner, $repoName, $isPrivate = false) {
    $url = "https://api.github.com/user/repos";
    $payload = [
        'name' => $repoName,
        'private' => $isPrivate,
        'auto_init' => false
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token {$token}",
        "Content-Type: application/json",
        "User-Agent: PHP-GitHub-Uploader"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Create repository failed: HTTP {$httpCode}");
    }
    
    return json_decode($response, true);
}

// Proses file ZIP
function processZipFile($zipPath, $token, $owner, $repo, $progressCallback = null) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new Exception("Cannot open ZIP file");
    }
    
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat['is_dir']) {
            $fileList[] = [
                'path' => $stat['name'],
                'index' => $i
            ];
        }
    }
    
    if (empty($fileList)) {
        $zip->close();
        throw new Exception("Tidak ada file yang ditemukan dalam ZIP");
    }
    
    $uploaded = 0;
    $failed = 0;
    $results = [];
    $total = count($fileList);
    
    foreach ($fileList as $idx => $file) {
        $filePath = $file['path'];
        try {
            $content = $zip->getFromIndex($file['index']);
            if ($content === false) {
                throw new Exception("Cannot read file content");
            }
            
            $isBinary = isBinaryFile($filePath);
            $base64Content = base64_encode($content);
            
            uploadToGitHub($token, $owner, $repo, $filePath, $base64Content, true);
            $uploaded++;
            $results[] = ['path' => $filePath, 'status' => 'success'];
            
            // Progress callback
            if ($progressCallback && (($idx + 1) % 5 === 0 || $idx + 1 === $total)) {
                $progressCallback($uploaded, $total);
            }
            
            delay(200); // Rate limit protection
            
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

// Fungsi utama handler
function githubUploadHandler($args, $replyFunc = null, $reactFunc = null) {
    try {
        // Cek parameter
        if (empty($args)) {
            $message = "⚠️ *GITHUB UPLOAD & EXTRACTOR*\n\n" .
                       "Upload file ZIP ke GitHub dan ekstrak otomatis.\n\n" .
                       "*Format:*\n" .
                       "github_upload <token>|<owner>|<repo>|<mode>\n\n" .
                       "*Parameter:*\n" .
                       "▸ token - GitHub Personal Access Token (repo scope)\n" .
                       "▸ owner - Username GitHub\n" .
                       "▸ repo - Nama repository\n" .
                       "▸ mode - new (buat baru) atau existing (pakai yg ada)\n\n" .
                       "*Contoh:*\n" .
                       "github_upload ghp_xxxxx|jerexd|my-repo|new\n\n" .
                       "*Upload file ZIP:*\n" .
                       "Reply file ZIP dengan caption di atas";
            return $replyFunc ? $replyFunc($message) : $message;
        }
        
        // Parse parameter
        $parts = explode('|', $args);
        if (count($parts) !== 4) {
            $message = "❌ *Format salah!*\n\n" .
                       "Gunakan: github_upload <token>|<owner>|<repo>|<mode>\n\n" .
                       "Contoh: github_upload ghp_xxxxx|jerexd|my-repo|new";
            return $replyFunc ? $replyFunc($message) : $message;
        }
        
        list($token, $owner, $repoName, $repoType) = array_map('trim', $parts);
        
        if ($repoType !== 'new' && $repoType !== 'existing') {
            $message = "❌ Mode harus 'new' atau 'existing'!";
            return $replyFunc ? $replyFunc($message) : $message;
        }
        
        // Update status
        if ($reactFunc) $reactFunc('⏳');
        
        // Cek file ZIP dari input
        global $uploadedFile;
        if (!isset($uploadedFile) || !file_exists($uploadedFile)) {
            $message = "⚠️ Reply file ZIP yang akan diupload ke GitHub!";
            return $replyFunc ? $replyFunc($message) : $message;
        }
        
        $zipPath = $uploadedFile;
        $zipSizeMB = number_format(filesize($zipPath) / 1024 / 1024, 2);
        
        $message = "📥 *File ZIP diterima*\n📦 Ukuran: {$zipSizeMB} MB\n⏳ Memproses...";
        if ($replyFunc) $replyFunc($message);
        
        // Buat repository jika mode new
        if ($repoType === 'new') {
            $message = "📁 Membuat repository baru: {$repoName}...";
            if ($replyFunc) $replyFunc($message);
            createRepository($token, $owner, $repoName, false);
            $message = "✅ Repository {$repoName} berhasil dibuat";
            if ($replyFunc) $replyFunc($message);
            delay(2000);
        } else {
            $message = "🔍 Mengecek repository {$repoName}...";
            if ($replyFunc) $replyFunc($message);
            
            // Cek repository
            $url = "https://api.github.com/repos/{$owner}/{$repoName}";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token {$token}",
                "User-Agent: PHP-GitHub-Uploader"
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("Repository {$repoName} tidak ditemukan atau token tidak memiliki akses");
            }
            $message = "✅ Repository ditemukan";
            if ($replyFunc) $replyFunc($message);
        }
        
        // Proses ZIP
        $message = "📂 Mengekstrak file ZIP...";
        if ($replyFunc) $replyFunc($message);
        
        // Progress callback
        $progressCallback = function($uploaded, $total) use ($replyFunc) {
            $msg = "📤 Progress: {$uploaded}/{$total} file diupload...";
            if ($replyFunc) $replyFunc($msg);
        };
        
        $result = processZipFile($zipPath, $token, $owner, $repoName, $progressCallback);
        
        // Hapus file temporary
        @unlink($zipPath);
        unset($uploadedFile);
        
        // Buat laporan
        $caption = "*📤 UPLOAD SELESAI*\n\n";
        $caption .= "*Total file:* {$result['total']}\n";
        $caption .= "✓ *Berhasil:* {$result['uploaded']}\n";
        if ($result['failed'] > 0) {
            $caption .= "✗ *Gagal:* {$result['failed']}\n\n";
            $failedFiles = array_filter($result['results'], function($r) {
                return $r['status'] === 'failed';
            });
            $failedFiles = array_slice($failedFiles, 0, 5);
            if (!empty($failedFiles)) {
                $caption .= "*File gagal:*\n";
                foreach ($failedFiles as $f) {
                    $caption .= "▸ {$f['path']}\n";
                }
                if ($result['failed'] > 5) {
                    $caption .= "▸ dan " . ($result['failed'] - 5) . " file lainnya\n";
                }
            }
        }
        $caption .= "\n🔗 *Repository:* https://github.com/{$owner}/{$repoName}";
        
        if ($reactFunc) $reactFunc('✅');
        return $replyFunc ? $replyFunc($caption) : $caption;
        
    } catch (Exception $e) {
        if ($reactFunc) $reactFunc('❌');
        $message = "❌ Error: " . $e->getMessage();
        return $replyFunc ? $replyFunc($message) : $message;
    }
}

/**
 * FUNGSI MAIN - Untuk dipanggil dari bot
 * 
 * @param string $args - Parameter: token|owner|repo|mode
 * @param string $filePath - Path file ZIP yang diupload
 * @param callable $replyFunc - Fungsi untuk reply pesan
 * @param callable $reactFunc - Fungsi untuk react emoji
 * @return string|null
 */
function main($args, $filePath = null, $replyFunc = null, $reactFunc = null) {
    global $uploadedFile;
    
    // Set file upload jika ada
    if ($filePath && file_exists($filePath)) {
        $uploadedFile = $filePath;
    }
    
    return githubUploadHandler($args, $replyFunc, $reactFunc);
}

// Contoh penggunaan jika dijalankan langsung
if (php_sapi_name() === 'cli') {
    $args = $argv[1] ?? '';
    $filePath = $argv[2] ?? '';
    
    if (empty($args)) {
        echo "Usage: php github_upload.php <token>|<owner>|<repo>|<mode> [file_path]\n";
        echo "Example: php github_upload.php ghp_xxxxx|jerexd|my-repo|new /path/to/file.zip\n";
        exit(1);
    }
    
    $result = main($args, $filePath, function($msg) {
        echo $msg . "\n";
    }, function($emoji) {
        echo "React: {$emoji}\n";
    });
    
    echo $result . "\n";
}

?>
