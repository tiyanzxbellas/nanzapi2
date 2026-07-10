<?php

/**
 * GITHUB UPLOAD & EXTRACTOR
 * Upload file ZIP ke GitHub lalu ekstrak otomatis ke repository.
 *
 * Format text:
 * token|owner|repo|mode
 *
 * mode:
 * - new
 * - existing
 */

function replyMsg(string $text): void
{
    // Ganti ini dengan reply bot kamu
    echo $text . PHP_EOL;
}

function reactMsg(string $emoji): void
{
    // Ganti ini dengan react bot kamu
    echo "[REACTION] {$emoji}" . PHP_EOL;
}

function githubRequest(
    string $method,
    string $url,
    string $token,
    ?array $body = null
): array {
    $ch = curl_init();

    $headers = [
        "Authorization: Bearer {$token}",
        "Accept: application/vnd.github+json",
        "Content-Type: application/json",
        "X-GitHub-Api-Version: 2022-11-28",
        "User-Agent: PHP-Github-Zip-Uploader"
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$err}");
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode >= 400) {
        $message = $data['message'] ?? $response;
        throw new Exception("GitHub API Error {$httpCode}: {$message}");
    }

    return $data ?: [];
}

function encodeGitHubPath(string $filePath): string
{
    $segments = explode('/', str_replace('\\', '/', $filePath));

    $encoded = array_map(function ($segment) {
        return rawurlencode($segment);
    }, $segments);

    return implode('/', $encoded);
}

function isUnsafeZipPath(string $path): bool
{
    $path = str_replace('\\', '/', $path);

    return str_starts_with($path, '/') ||
        str_contains($path, '../') ||
        str_contains($path, '..\\') ||
        $path === '..' ||
        str_starts_with($path, '../');
}

function uploadToGitHub(
    string $token,
    string $owner,
    string $repo,
    string $filePath,
    string $contentBase64
): array {
    $encodedPath = encodeGitHubPath($filePath);

    $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$encodedPath}";

    $sha = null;

    try {
        $check = githubRequest('GET', $url, $token);
        if (isset($check['sha'])) {
            $sha = $check['sha'];
        }
    } catch (Exception $e) {
        // Kalau file belum ada, lanjut upload baru
    }

    $payload = [
        'message' => $sha ? "Update {$filePath}" : "Add {$filePath}",
        'content' => $contentBase64,
    ];

    if ($sha) {
        $payload['sha'] = $sha;
    }

    return githubRequest('PUT', $url, $token, $payload);
}

function getAuthenticatedUser(string $token): array
{
    return githubRequest('GET', 'https://api.github.com/user', $token);
}

function createRepository(
    string $token,
    string $owner,
    string $repoName,
    bool $private = false
): array {
    $user = getAuthenticatedUser($token);
    $login = $user['login'] ?? null;

    $payload = [
        'name' => $repoName,
        'private' => $private,
        'auto_init' => false,
    ];

    // Kalau owner sama dengan akun token, buat repo user biasa
    if ($login && strtolower($login) === strtolower($owner)) {
        return githubRequest(
            'POST',
            'https://api.github.com/user/repos',
            $token,
            $payload
        );
    }

    // Kalau owner beda, diasumsikan owner adalah organisasi
    return githubRequest(
        'POST',
        "https://api.github.com/orgs/{$owner}/repos",
        $token,
        $payload
    );
}

function checkRepository(
    string $token,
    string $owner,
    string $repoName
): bool {
    githubRequest(
        'GET',
        "https://api.github.com/repos/{$owner}/{$repoName}",
        $token
    );

    return true;
}

function processZipFile(
    string $zipPath,
    string $token,
    string $owner,
    string $repo
): array {
    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true) {
        throw new Exception('Gagal membuka file ZIP');
    }

    $fileList = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $name = $stat['name'] ?? '';

        if (!$name || str_ends_with($name, '/')) {
            continue;
        }

        if (isUnsafeZipPath($name)) {
            continue;
        }

        $fileList[] = [
            'index' => $i,
            'path' => $name,
        ];
    }

    if (count($fileList) === 0) {
        $zip->close();
        throw new Exception('Tidak ada file yang ditemukan dalam ZIP');
    }

    $uploaded = 0;
    $failed = 0;
    $results = [];

    foreach ($fileList as $i => $file) {
        $filePath = $file['path'];

        try {
            $content = $zip->getFromIndex($file['index']);

            if ($content === false) {
                throw new Exception('Gagal membaca file dari ZIP');
            }

            $base64 = base64_encode($content);

            uploadToGitHub(
                $token,
                $owner,
                $repo,
                $filePath,
                $base64
            );

            $uploaded++;

            $results[] = [
                'path' => $filePath,
                'status' => 'success',
            ];

            if ((($i + 1) % 5 === 0) || ($i === count($fileList) - 1)) {
                replyMsg("📤 Progress: {$uploaded}/" . count($fileList) . " file diupload...");
            }

            usleep(200000); // delay 200ms

        } catch (Exception $e) {
            $failed++;

            $results[] = [
                'path' => $filePath,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    $zip->close();

    return [
        'uploaded' => $uploaded,
        'failed' => $failed,
        'total' => count($fileList),
        'results' => $results,
    ];
}

function githubUp(string $zipPath, string $text): void
{
    try {
        if (!file_exists($zipPath)) {
            replyMsg("⚠️ File ZIP tidak ditemukan!");
            return;
        }

        if (!str_ends_with(strtolower($zipPath), '.zip')) {
            replyMsg("⚠️ File harus berformat ZIP!");
            return;
        }

        if (!$text) {
            replyMsg(
                "GITHUB UPLOAD & EXTRACTOR\n\n" .
                "Upload file ZIP ke GitHub dan ekstrak otomatis.\n\n" .
                "Format:\n" .
                "githubup <token>|<owner>|<repo>|<mode>\n\n" .
                "Parameter:\n" .
                "▸ token - GitHub Personal Access Token repo scope\n" .
                "▸ owner - Username atau organisasi GitHub\n" .
                "▸ repo - Nama repository\n" .
                "▸ mode - new atau existing\n\n" .
                "Contoh:\n" .
                "githubup ghp_xxxxx|jerexd|my-repo|new"
            );
            return;
        }

        $parts = array_map('trim', explode('|', $text));

        $token = $parts[0] ?? null;
        $owner = $parts[1] ?? null;
        $repoName = $parts[2] ?? null;
        $repoType = $parts[3] ?? null;

        if (!$token || !$owner || !$repoName || !$repoType) {
            replyMsg(
                "❌ Format salah!\n\n" .
                "Gunakan:\n" .
                "githubup <token>|<owner>|<repo>|<mode>\n\n" .
                "Contoh:\n" .
                "githubup ghp_xxxxx|jerexd|my-repo|new"
            );
            return;
        }

        if (!in_array($repoType, ['new', 'existing'], true)) {
            replyMsg("❌ Mode harus 'new' atau 'existing'!");
            return;
        }

        reactMsg('⏳');

        $zipSizeMB = number_format(filesize($zipPath) / 1024 / 1024, 2);

        replyMsg(
            "📥 File ZIP diterima\n" .
            "📦 Ukuran: {$zipSizeMB} MB\n" .
            "⏳ Memproses..."
        );

        if ($repoType === 'new') {
            replyMsg("📁 Membuat repository baru: {$repoName}...");

            createRepository($token, $owner, $repoName, false);

            replyMsg("✅ Repository {$repoName} berhasil dibuat");

            sleep(2);
        } else {
            replyMsg("🔍 Mengecek repository {$repoName}...");

            checkRepository($token, $owner, $repoName);

            replyMsg("✅ Repository ditemukan");
        }

        replyMsg("📂 Mengekstrak file ZIP...");

        $result = processZipFile(
            $zipPath,
            $token,
            $owner,
            $repoName
        );

        $caption = "UPLOAD SELESAI\n\n";
        $caption .= "Total file: {$result['total']}\n";
        $caption .= "✓ Berhasil: {$result['uploaded']}\n";

        if ($result['failed'] > 0) {
            $caption .= "X Gagal: {$result['failed']}\n\n";

            $failedFiles = array_values(array_filter(
                $result['results'],
                fn ($r) => $r['status'] === 'failed'
            ));

            $caption .= "*File gagal:*\n";

            foreach (array_slice($failedFiles, 0, 5) as $file) {
                $caption .= "▸ {$file['path']}\n";
            }

            if ($result['failed'] > 5) {
                $more = $result['failed'] - 5;
                $caption .= "▸ dan {$more} file lainnya\n";
            }
        }

        $caption .= "\nLink Repository: https://github.com/{$owner}/{$repoName}";

        replyMsg($caption);

        reactMsg('✅');

    } catch (Exception $e) {
        reactMsg('❌');
        replyMsg("❌ Error: " . $e->getMessage());
    }
}

/**
 * CONTOH PEMAKAIAN CLI
 *
 * php githubup.php file.zip "ghp_xxxxx|username|repo-name|new"
 */
if (php_sapi_name() === 'cli') {
    $zipPath = $argv[1] ?? null;
    $text = $argv[2] ?? null;

    if (!$zipPath || !$text) {
        echo "Usage:\n";
        echo "php githubup.php file.zip \"token|owner|repo|new\"\n";
        exit;
    }

    githubUp($zipPath, $text);
}
