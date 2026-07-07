<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Upload ZIP & Ekstrak Langsung ke GitHub via Token GH
// Fitur: Upload file .ZIP, ekstrak otomatis, upload ke GitHub repository
// Parameter: token|owner|repo|mode
// Contoh penggunaan: 
// - Buat repo baru: {"file": "project.zip", "token": "ghp_xxxxx", "owner": "username", "repo": "new-repo", "mode": "new"}
// - Upload ke repo existing: {"file": "project.zip", "token": "ghp_xxxxx", "owner": "username", "repo": "existing-repo", "mode": "existing"}

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];

try {
    // Cek parameter yang diperlukan
    $requiredParams = ['token', 'owner', 'repo', 'mode'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            throw new Exception("Parameter '$param' wajib diisi\n\nFormat: token|owner|repo|mode\nContoh: ghp_xxxxx|jerexd|my-repo|new");
        }
    }

    $token = $_POST['token'];
    $owner = $_POST['owner'];
    $repoName = $_POST['repo'];
    $mode = $_POST['mode'];
    $branch = isset($_POST['branch']) ? $_POST['branch'] : 'main';

    // Validasi mode
    if ($mode !== 'new' && $mode !== 'existing') {
        throw new Exception("Mode harus 'new' (buat baru) atau 'existing' (pakai yg ada)");
    }

    // Cek file ZIP
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File ZIP wajib diupload! Reply file ZIP dengan caption yang sesuai');
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);

    // Validasi ekstensi file
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExt !== 'zip') {
        throw new Exception('Hanya file ZIP yang diperbolehkan! File Anda: ' . $fileName);
    }

    if ($fileSize > 100 * 1024 * 1024) { // Maks 100MB
        throw new Exception('File ZIP terlalu besar (max 100MB) - Ukuran Anda: ' . $fileSizeMB . 'MB');
    }

    // Baca file ZIP
    $zipContent = file_get_contents($fileTmp);
    if ($zipContent === false) {
        throw new Exception('Gagal membaca file ZIP');
    }

    // ========== FUNGSI GITHUB API ==========
    
    // Fungsi untuk membuat repository
    function createRepository($token, $owner, $repoName, $isPrivate = false) {
        $url = 'https://api.github.com/user/repos';
        $data = json_encode([
            'name' => $repoName,
            'private' => $isPrivate,
            'auto_init' => true // Set auto_init true agar branch main langsung ada
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'Content-Type: application/json',
            'User-Agent: PHP-GitHub-Uploader'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('CURL Error (create repo): ' . $error);
        }

        if ($http_code !== 201) {
            $errMsg = json_decode($response, true);
            throw new Exception('Gagal membuat repository: ' . ($errMsg['message'] ?? $response));
        }

        return json_decode($response, true);
    }

    // Fungsi untuk cek repository
    function checkRepository($token, $owner, $repoName) {
        $url = "https://api.github.com/repos/{$owner}/{$repoName}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'User-Agent: PHP-GitHub-Uploader'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200;
    }

    // Fungsi untuk mendapatkan default branch
    function getDefaultBranch($token, $owner, $repoName) {
        $url = "https://api.github.com/repos/{$owner}/{$repoName}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'User-Agent: PHP-GitHub-Uploader'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            return $data['default_branch'] ?? 'main';
        }
        return 'main';
    }

    // Fungsi untuk upload file ke GitHub
    function uploadToGitHub($token, $owner, $repo, $filePath, $content, $branch = 'main') {
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            $sha = $data['sha'] ?? null;
        }

        // Upload/Update file
        $postData = json_encode([
            'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
            'content' => $content,
            'branch' => $branch,
            'sha' => $sha
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'Content-Type: application/json',
            'User-Agent: PHP-GitHub-Uploader'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('CURL Error (upload): ' . $error);
        }

        if ($http_code !== 201 && $http_code !== 200) {
            $errMsg = json_decode($response, true);
            throw new Exception('Gagal upload file: ' . ($errMsg['message'] ?? $response));
        }

        return json_decode($response, true);
    }

    // Fungsi untuk mengecek apakah file binary
    function isBinaryFile($filename) {
        $binaryExtensions = [
            '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.ico', '.webp',
            '.pdf', '.zip', '.rar', '.7z', '.tar', '.gz',
            '.exe', '.dll', '.so', '.dylib', '.bin', '.dat',
            '.mp3', '.mp4', '.avi', '.mov', '.mkv', '.flv',
            '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
            '.woff', '.woff2', '.ttf', '.eot', '.otf',
            '.jpg', '.jpeg', '.png', '.gif', '.svg'
        ];
        $lowerName = strtolower($filename);
        foreach ($binaryExtensions as $ext) {
            if (substr($lowerName, -strlen($ext)) === $ext) {
                return true;
            }
        }
        return false;
    }

    // ========== PROSES ZIP ==========
    function processZipFile($zipContent, $token, $owner, $repo, $branch = 'main') {
        $tempZip = tempnam(sys_get_temp_dir(), 'zip_');
        file_put_contents($tempZip, $zipContent);

        $zip = new ZipArchive();
        if ($zip->open($tempZip) !== true) {
            unlink($tempZip);
            throw new Exception('Gagal membuka file ZIP');
        }

        $uploaded = 0;
        $failed = 0;
        $results = [];
        $totalFiles = $zip->numFiles;

        for ($i = 0; $i < $totalFiles; $i++) {
            $fileInfo = $zip->statIndex($i);
            if ($fileInfo === false || $fileInfo['size'] === 0) continue;

            $filePath = $fileInfo['name'];
            // Skip directory entries
            if (substr($filePath, -1) === '/') continue;

            try {
                $fileContent = $zip->getFromIndex($i);
                if ($fileContent === false) {
                    throw new Exception('Gagal membaca file dari ZIP');
                }

                $base64Content = base64_encode($fileContent);

                uploadToGitHub($token, $owner, $repo, $filePath, $base64Content, $branch);
                $uploaded++;
                $results[] = ['path' => $filePath, 'status' => 'success'];

                // Delay untuk menghindari rate limit
                usleep(300000); // 300ms

            } catch (Exception $e) {
                $failed++;
                $results[] = ['path' => $filePath, 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        $zip->close();
        unlink($tempZip);

        return [
            'uploaded' => $uploaded,
            'failed' => $failed,
            'total' => $totalFiles,
            'results' => $results
        ];
    }

    // ========== EKSEKUSI ==========
    
    // Buat repository jika mode new
    if ($mode === 'new') {
        // Cek apakah repo sudah ada
        $repoExists = checkRepository($token, $owner, $repoName);
        if ($repoExists) {
            throw new Exception("Repository '{$repoName}' sudah ada. Gunakan mode 'existing' atau nama repo lain.");
        }

        // Buat repository baru dengan auto_init true
        $createResult = createRepository($token, $owner, $repoName, false);
        if (!$createResult) {
            throw new Exception('Gagal membuat repository baru');
        }
        
        // Tunggu sebentar agar repository siap
        sleep(3);
        
        // Dapatkan default branch dari repository
        $actualBranch = getDefaultBranch($token, $owner, $repoName);
        if ($actualBranch) {
            $branch = $actualBranch;
        }
    } else {
        // Cek apakah repo exists
        $repoExists = checkRepository($token, $owner, $repoName);
        if (!$repoExists) {
            throw new Exception("Repository '{$repoName}' tidak ditemukan atau token tidak memiliki akses");
        }
        
        // Dapatkan default branch
        $actualBranch = getDefaultBranch($token, $owner, $repoName);
        if ($actualBranch) {
            $branch = $actualBranch;
        }
    }

    // Proses file ZIP
    $result = processZipFile($zipContent, $token, $owner, $repoName, $branch);

    // Siapkan response
    $response = array_merge($credit, [
        'status' => true,
        'result' => [
            'repository' => "https://github.com/{$owner}/{$repoName}",
            'mode' => $mode,
            'branch' => $branch,
            'total_files' => $result['total'],
            'uploaded' => $result['uploaded'],
            'failed' => $result['failed'],
            'details' => $result['results']
        ]
    ]);

    // Tambahkan informasi error jika ada
    if ($result['failed'] > 0) {
        $response['warnings'] = "{$result['failed']} file gagal diupload";
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT);
}
?>
