<?php
/*
 * [ GITHUB UPLOAD & EXTRACTOR - REST API ]
 * creator : Tiyanz
 * source  : https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33
 * 
 * Fitur: Upload file ZIP & ekstrak langsung ke GitHub via Token
 * Support: JSON API + multipart/form-data
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$credit = [
    'creator' => 'Tiyanz',
    'source' => 'https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33',
    'version' => '6.1-rest-api'
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
    
    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $name = $stat['name'];
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
    $token = null;
    $owner = null;
    $repoName = null;
    $repoType = null;
    $zipBuffer = null;
    $fileName = null;

    // === CEK METODE REQUEST ===
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Tampilkan form HTML untuk testing
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>GitHub Upload API - Tiyanz</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #0d1117; color: #c9d1d9; }
                .container { background: #161b22; padding: 30px; border-radius: 10px; border: 1px solid #30363d; }
                h1 { color: #58a6ff; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; font-weight: bold; color: #8b949e; }
                input, select, textarea { width: 100%; padding: 10px; background: #0d1117; border: 1px solid #30363d; border-radius: 5px; color: #c9d1d9; font-size: 14px; }
                input:focus, select:focus, textarea:focus { outline: none; border-color: #58a6ff; }
                button { background: #238636; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
                button:hover { background: #2ea043; }
                .note { background: #1f2937; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 13px; color: #8b949e; }
                .note code { background: #0d1117; padding: 2px 6px; border-radius: 3px; color: #f0883e; }
                .result { margin-top: 20px; padding: 15px; background: #0d1117; border-radius: 5px; border: 1px solid #30363d; white-space: pre-wrap; word-break: break-all; font-size: 12px; max-height: 500px; overflow: auto; }
                .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
                .badge-success { background: #238636; color: white; }
                .badge-danger { background: #da3633; color: white; }
                .badge-warning { background: #d29922; color: white; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📤 GitHub Upload & Extractor</h1>
                <p style="color: #8b949e;">Upload file ZIP & ekstrak langsung ke GitHub</p>
                <hr style="border-color: #30363d;">
                
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>🔑 GitHub Token</label>
                        <input type="text" name="token" placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
                        <small style="color: #8b949e;">Personal Access Token (repo scope)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>👤 Owner / Username</label>
                        <input type="text" name="owner" placeholder="username_github" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📁 Repository Name</label>
                        <input type="text" name="repo" placeholder="my-repo" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📌 Mode</label>
                        <select name="mode" required>
                            <option value="new">🆕 New - Buat repository baru</option>
                            <option value="existing">📂 Existing - Pakai repository yang sudah ada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📦 File ZIP</label>
                        <input type="file" name="file" accept=".zip" required>
                        <small style="color: #8b949e;">Max 20MB</small>
                    </div>
                    
                    <button type="submit">🚀 Upload ke GitHub</button>
                </form>
                
                <div class="note">
                    <strong>📌 Info:</strong><br>
                    • Token harus memiliki akses <code>repo</code><br>
                    • Mode <code>new</code> akan membuat repository baru<br>
                    • Mode <code>existing</code> upload ke repository yang sudah ada<br>
                    • 1 ZIP = SEMUA FILE LANGSUNG DIUPLOAD!<br>
                    • Source: <a href="https://whatsapp.com/channel/0029VbAo3iNAjPXTxx0Luv33" target="_blank" style="color: #58a6ff;">Tiyanz Channel</a>
                </div>
                
                <div id="result" class="result" style="display:none;"></div>
            </div>
            
            <script>
                document.getElementById('uploadForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const resultDiv = document.getElementById('result');
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '⏳ Uploading...';
                    resultDiv.style.color = '#d29922';
                    
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        resultDiv.style.color = '#c9d1d9';
                        resultDiv.innerHTML = JSON.stringify(data, null, 2);
                        
                        if (data.status === true) {
                            resultDiv.style.borderColor = '#238636';
                        } else {
                            resultDiv.style.borderColor = '#da3633';
                        }
                    } catch (error) {
                        resultDiv.style.color = '#da3633';
                        resultDiv.innerHTML = '❌ Error: ' + error.message;
                    }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // === POST: Cek apakah multipart atau JSON ===
    
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // === MULTIPART FORM DATA ===
        
        if (!isset($_POST['token']) || !isset($_POST['owner']) || !isset($_POST['repo']) || !isset($_POST['mode'])) {
            throw new Exception("Parameter wajib: token, owner, repo, mode");
        }
        
        $token = trim($_POST['token']);
        $owner = trim($_POST['owner']);
        $repoName = trim($_POST['repo']);
        $repoType = trim($_POST['mode']);
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File ZIP wajib diupload');
        }
        
        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        
        // Validasi file
        $fileMime = mime_content_type($fileTmp);
        if (!strpos($fileMime, 'zip') && !strpos($fileName, '.zip')) {
            throw new Exception('File harus berformat ZIP');
        }
        
        $maxSize = 20 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            throw new Exception('File ZIP terlalu besar (max 20MB)');
        }
        
        $zipBuffer = file_get_contents($fileTmp);
        
    } else {
        // === JSON API ===
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input. Use multipart/form-data for file upload or send valid JSON.');
        }
        
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
        
        if (empty($input['file'])) {
            throw new Exception('File ZIP (base64) wajib diisi');
        }
        
        $zipBuffer = base64_decode($input['file']);
        if ($zipBuffer === false || empty($zipBuffer)) {
            throw new Exception('File ZIP base64 tidak valid atau kosong');
        }
    }
    
    // === VALIDASI UMUM ===
    
    if (!preg_match('/^ghp_[a-zA-Z0-9]{36}$/', $token) && !preg_match('/^github_pat_[a-zA-Z0-9_]+$/', $token)) {
        throw new Exception('Format token GitHub tidak valid. Harus dimulai dengan ghp_ atau github_pat_');
    }
    
    if ($repoType !== 'new' && $repoType !== 'existing') {
        throw new Exception("Mode harus 'new' atau 'existing'!");
    }
    
    // Validasi ZIP header
    $zipHeader = substr($zipBuffer, 0, 4);
    if ($zipHeader !== "PK\x03\x04" && $zipHeader !== "PK\x05\x06" && $zipHeader !== "PK\x07\x08") {
        throw new Exception('File bukan ZIP yang valid');
    }
    
    $fileSize = strlen($zipBuffer);
    $maxSize = 20 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception('File ZIP terlalu besar (max 20MB)');
    }
    
    $zipSizeMB = number_format($fileSize / 1024 / 1024, 2);
    
    // === PROSES UPLOAD ===
    
    $repo = $repoName;
    
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
    
    $defaultBranch = getDefaultBranch($token, $owner, $repo);
    $result = processZipFile($zipBuffer, $token, $owner, $repo);
    
    // === RESPONSE ===
    
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
