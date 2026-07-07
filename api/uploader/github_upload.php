<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * base    : https://github.com
 * support : me with follow my channel
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 */

error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: GitHub Upload & Extractor (ZIP to Repository)
// Contoh: {"token": "ghp_xxxxx", "owner": "username", "repo": "repo-name", "mode": "new"}

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33'
];

// ========== FUNGSI HELPER ==========

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
        if (substr($lowerName, -strlen($ext)) === $ext) {
            return true;
        }
    }
    return false;
}

// Buffer ke Base64
function bufferToBase64($buffer) {
    return base64_encode($buffer);
}

// Delay function
function delay($ms) {
    usleep($ms * 1000);
}

// ========== FUNGSI GITHUB API ==========

// Upload ke GitHub
function uploadToGitHub($token, $owner, $repo, $filePath, $content, $isBase64 = true) {
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $filePath)));
    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";
    
    // Cek apakah file sudah ada
    $sha = null;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['sha'])) {
            $sha = $data['sha'];
        }
    }
    
    // Upload/Update file
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
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("Gagal upload {$filePath}: HTTP {$httpCode}");
    }
    
    return json_decode($response, true);
}

// Create Repository
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
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("Gagal membuat repository: HTTP {$httpCode} - {$response}");
    }
    
    return json_decode($response, true);
}

// Cek repository exist
function checkRepository($token, $owner, $repoName) {
    $url = "https://api.github.com/repos/{$owner}/{$repoName}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-GitHub-Uploader'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// ========== PROSES ZIP ==========

function processZipFile($zipBuffer, $token, $owner, $repo, $progressCallback = null) {
    $zip = new ZipArchive();
    $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
    file_put_contents($tempZip, $zipBuffer);
    
    if ($zip->open($tempZip) !== true) {
        unlink($tempZip);
        throw new Exception('Gagal membuka file ZIP');
    }
    
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat['size'] && substr($stat['name'], -1) === '/') {
            continue; // Skip folder
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
    
    foreach ($fileList as $index => $file) {
        try {
            $filePath = $file['name'];
            $isBinary = isBinaryFile($filePath);
            
            $content = $zip->getFromIndex($file['index']);
            if ($content === false) {
                throw new Exception('Gagal membaca file dari ZIP');
            }
            
            $base64Content = bufferToBase64($content);
            uploadToGitHub($token, $owner, $repo, $filePath, $base64Content, true);
            
            $uploaded++;
            $results[] = ['path' => $filePath, 'status' => 'success'];
            
            // Progress callback
            if ($progressCallback && ($uploaded % 5 === 0 || $uploaded === $total)) {
                $progressCallback($uploaded, $total);
            }
            
            delay(200); // Rate limit
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
    // Cek parameter
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
    
    // Cek mime type
    $fileMime = mime_content_type($fileTmp);
    if (!strpos($fileMime, 'zip') && !strpos($fileName, '.zip')) {
        throw new Exception('File harus berformat ZIP');
    }
    
    if ($fileSize > 100 * 1024 * 1024) { // 100MB max
        throw new Exception('File ZIP terlalu besar (max 100MB)');
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
    
    // Proses ZIP
    $result = processZipFile($zipBuffer, $token, $owner, $repo, function($uploaded, $total) {
        // Progress callback (bisa ditambahkan ke log)
        error_log("Progress: {$uploaded}/{$total} files uploaded");
    });
    
    // ========== RESPONSE ==========
    
    $response = [
        'creator' => 'Tiyanz',
        'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
        'status' => true,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repo}",
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed']
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
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]));
}
?>
