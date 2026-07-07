<?php
/**
 * Web2ApkService
 * Sistem untuk mengubah website jadi APK via webappcreator.amethystlab.org
 * Support upload file dan testing endpoint API
 * 
 * source saluran:
 * https://whatsapp.com/channel/0029Vb6P2e1E50UZYaX4wI0W
 */

class Web2ApkService
{
    private $apiUrl;
    private $baseUrl;

    public function __construct($config = [])
    {
        $this->apiUrl = $config['apiUrl'] ?? 'https://webappcreator.amethystlab.org/api/build-apk';
        $this->baseUrl = $config['baseUrl'] ?? 'https://webappcreator.amethystlab.org';
    }

    /**
     * Validasi URL
     */
    public function isValidUrl($url)
    {
        return preg_match('/^https?:\/\//i', $url) === 1;
    }

    /**
     * Membuat package name dari nama aplikasi
     */
    public function buildPackageName($appName)
    {
        $cleaned = strtolower(preg_replace('/[^a-z0-9]/', '', $appName));
        return 'com.' . ($cleaned ?: 'app') . '.web2apk';
    }

    /**
     * Upload file icon dari berbagai sumber
     */
    public function handleIconUpload($fileInput)
    {
        // Cek apakah ini upload file dari form
        if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES[$fileInput]['tmp_name'];
            $fileType = $_FILES[$fileInput]['type'];
            
            // Validasi tipe file
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('File harus berupa gambar (PNG, JPG, JPEG, WEBP)');
            }
            
            // Validasi ukuran (max 5MB)
            if ($_FILES[$fileInput]['size'] > 5 * 1024 * 1024) {
                throw new Exception('Ukuran file maksimal 5MB');
            }
            
            return file_get_contents($fileTmp);
        }
        
        // Cek apakah ini URL gambar
        if (isset($_POST['icon_url']) && filter_var($_POST['icon_url'], FILTER_VALIDATE_URL)) {
            $iconContent = file_get_contents($_POST['icon_url']);
            if ($iconContent === false) {
                throw new Exception('Gagal mengunduh icon dari URL');
            }
            return $iconContent;
        }
        
        // Cek apakah ini base64 image
        if (isset($_POST['icon_base64']) && !empty($_POST['icon_base64'])) {
            $base64Data = $_POST['icon_base64'];
            // Hapus prefix jika ada
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            $decoded = base64_decode($base64Data);
            if ($decoded === false) {
                throw new Exception('Format base64 tidak valid');
            }
            return $decoded;
        }
        
        throw new Exception('Tidak ada icon yang diupload. Upload file, berikan URL, atau base64.');
    }

    /**
     * Save icon buffer ke file temporary
     */
    private function saveIconBuffer($buffer)
    {
        $tempDir = sys_get_temp_dir() . '/web2apk';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $iconPath = $tempDir . '/icon_' . time() . '_' . uniqid() . '.png';
        file_put_contents($iconPath, $buffer);
        return $iconPath;
    }

    /**
     * Build APK dari website
     */
    public function build($params)
    {
        $url = $params['url'] ?? null;
        $appName = $params['appName'] ?? null;
        $iconBuffer = $params['iconBuffer'] ?? null;
        $versionName = $params['versionName'] ?? '1.0.0';
        $versionCode = $params['versionCode'] ?? 1;

        // Validasi
        if (!$this->isValidUrl($url)) {
            throw new Exception('URL harus diawali dengan http:// atau https://');
        }
        if (empty($appName)) {
            throw new Exception('Nama aplikasi tidak boleh kosong.');
        }
        if (empty($iconBuffer)) {
            throw new Exception('Icon aplikasi wajib disertakan.');
        }

        $packageName = $this->buildPackageName($appName);
        $iconPath = $this->saveIconBuffer($iconBuffer);

        try {
            // Siapkan multipart form data
            $postFields = [
                'websiteUrl' => $url,
                'appName' => $appName,
                'packageName' => $packageName,
                'versionName' => $versionName,
                'versionCode' => $versionCode,
            ];

            // Siapkan file untuk upload
            $filePath = realpath($iconPath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            $boundary = uniqid('--------------------------', true);
            $body = '';

            // Tambahkan field-form
            foreach ($postFields as $key => $value) {
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                $body .= $value . "\r\n";
            }

            // Tambahkan file icon
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"icon\"; filename=\"" . basename($iconPath) . "\"\r\n";
            $body .= "Content-Type: $mimeType\r\n\r\n";
            $body .= file_get_contents($iconPath) . "\r\n";
            $body .= "--$boundary--\r\n";

            // Siapkan header
            $headers = [
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Origin: ' . $this->baseUrl,
                'Referer: ' . $this->baseUrl . '/',
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Content-Length: ' . strlen($body),
            ];

            // Kirim request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('CURL Error: ' . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode . ' - ' . $response);
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg() . ' - Response: ' . $response);
            }

            if (!isset($data['success']) || $data['success'] !== true) {
                throw new Exception($data['message'] ?? 'Gagal mem-build APK dari server.');
            }

            if (!isset($data['downloadUrl'])) {
                throw new Exception('Download URL tidak ditemukan dalam response.');
            }

            return [
                'success' => true,
                'appName' => $appName,
                'packageName' => $packageName,
                'downloadUrl' => $this->baseUrl . $data['downloadUrl'],
                'fullResponse' => $data
            ];
        } finally {
            // Hapus file temporary
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }
    }

    /**
     * Handle API Request
     */
    public function handleRequest()
    {
        header('Content-Type: application/json');
        
        try {
            // Cek method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method harus POST');
            }

            // Ambil parameter
            $url = $_POST['url'] ?? null;
            $appName = $_POST['appName'] ?? null;
            $versionName = $_POST['versionName'] ?? '1.0.0';
            $versionCode = (int) ($_POST['versionCode'] ?? 1);

            // Handle icon upload
            $iconBuffer = null;
            try {
                // Cek upload file
                if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
                    $iconBuffer = file_get_contents($_FILES['icon']['tmp_name']);
                } 
                // Cek URL icon
                elseif (isset($_POST['icon_url']) && filter_var($_POST['icon_url'], FILTER_VALIDATE_URL)) {
                    $iconContent = file_get_contents($_POST['icon_url']);
                    if ($iconContent !== false) {
                        $iconBuffer = $iconContent;
                    }
                }
                // Cek base64 icon
                elseif (isset($_POST['icon_base64']) && !empty($_POST['icon_base64'])) {
                    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $_POST['icon_base64']);
                    $decoded = base64_decode($base64Data);
                    if ($decoded !== false) {
                        $iconBuffer = $decoded;
                    }
                }
            } catch (Exception $e) {
                // Abaikan error, nanti akan dihandle di build
            }

            // Build APK
            $result = $this->build([
                'url' => $url,
                'appName' => $appName,
                'iconBuffer' => $iconBuffer,
                'versionName' => $versionName,
                'versionCode' => $versionCode,
            ]);

            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'data' => $result
            ], JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}

// ======================== ENDPOINT HANDLER ========================

// Jika ini diakses sebagai endpoint API
if (basename($_SERVER['SCRIPT_FILENAME']) === 'web2apk.php') {
    $service = new Web2ApkService();
    $service->handleRequest();
    exit;
}

// ======================== TESTING / STANDALONE ========================

function testWeb2Apk()
{
    echo "=== WEB2APK TESTING ===\n\n";
    
    $service = new Web2ApkService();
    
    try {
        // Method 1: Upload file dari local
        $iconPath = './icon.png'; // Ganti dengan path icon kamu
        
        if (!file_exists($iconPath)) {
            echo "⚠️  File icon tidak ditemukan: $iconPath\n";
            echo "📌  Buat file icon.png di direktori yang sama atau gunakan method lain\n\n";
            
            // Method 2: Download dari URL
            $iconUrl = 'https://via.placeholder.com/512x512.png?text=APP';
            echo "📥  Mencoba download icon dari: $iconUrl\n";
            $iconBuffer = file_get_contents($iconUrl);
            
            if ($iconBuffer === false) {
                throw new Exception('Gagal download icon dari URL');
            }
            echo "✅  Icon berhasil didownload\n";
        } else {
            echo "📁  Membaca icon dari: $iconPath\n";
            $iconBuffer = file_get_contents($iconPath);
        }

        echo "\n🚀  Memulai build APK...\n";
        
        $result = $service->build([
            'url' => 'https://www.google.com',
            'appName' => 'Google App',
            'iconBuffer' => $iconBuffer,
            'versionName' => '1.0.0',
            'versionCode' => 1,
        ]);

        echo "\n✅  BERHASIL!\n";
        echo "📱  Nama App: " . $result['appName'] . "\n";
        echo "📦  Package: " . $result['packageName'] . "\n";
        echo "🔗  Download: " . $result['downloadUrl'] . "\n";
        print_r($result['fullResponse'] ?? []);

    } catch (Exception $err) {
        echo "\n❌  Gagal: " . $err->getMessage() . "\n";
    }
}

// ======================== HTML FORM TESTER ========================

function showForm()
{
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Web2Apk Tester</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 { color: #333; margin-top: 0; }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
                color: #555;
            }
            input, textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-sizing: border-box;
                font-size: 14px;
            }
            input[type="file"] {
                padding: 10px 0;
            }
            button {
                background: #007bff;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                width: 100%;
            }
            button:hover {
                background: #0056b3;
            }
            .result {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
                border-left: 4px solid #007bff;
                display: none;
            }
            .result.error {
                border-left-color: #dc3545;
                background: #f8d7da;
                color: #721c24;
            }
            .result.success {
                border-left-color: #28a745;
                background: #d4edda;
                color: #155724;
            }
            .loading {
                display: none;
                text-align: center;
                padding: 20px;
            }
            .spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #007bff;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .info {
                background: #e7f3ff;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-size: 14px;
                color: #004085;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📱 Web2Apk Tester</h1>
            <div class="info">
                <strong>Endpoint:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?><br>
                <strong>Method:</strong> POST
            </div>
            
            <form id="web2apkForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>URL Website *</label>
                    <input type="url" name="url" placeholder="https://example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Aplikasi *</label>
                    <input type="text" name="appName" placeholder="Nama App" required>
                </div>
                
                <div class="form-group">
                    <label>Icon Aplikasi * (PNG/JPG/WEBP, max 5MB)</label>
                    <input type="file" name="icon" accept="image/png,image/jpeg,image/webp">
                    <small style="color: #666;">Upload file atau gunakan URL/Base64 di bawah</small>
                </div>
                
                <div class="form-group">
                    <label>URL Icon (Opsional)</label>
                    <input type="url" name="icon_url" placeholder="https://example.com/icon.png">
                </div>
                
                <div class="form-group">
                    <label>Base64 Icon (Opsional)</label>
                    <textarea name="icon_base64" placeholder="data:image/png;base64,..." rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Version Name</label>
                    <input type="text" name="versionName" value="1.0.0">
                </div>
                
                <div class="form-group">
                    <label>Version Code</label>
                    <input type="number" name="versionCode" value="1">
                </div>
                
                <button type="submit">🚀 Build APK</button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Memproses build APK...</p>
            </div>
            
            <div class="result" id="result"></div>
        </div>
        
        <script>
        document.getElementById('web2apkForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            const loadingDiv = document.getElementById('loading');
            
            resultDiv.style.display = 'none';
            loadingDiv.style.display = 'block';
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                loadingDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Build Berhasil!</strong><br><br>
                        <strong>Nama:</strong> ${data.data.appName}<br>
                        <strong>Package:</strong> ${data.data.packageName}<br>
                        <strong>Download:</strong> <a href="${data.data.downloadUrl}" target="_blank">${data.data.downloadUrl}</a>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `<strong>❌ Error:</strong> ${data.message}`;
                }
            } catch (error) {
                loadingDiv.style.display = 'none';
                resultDiv.className = 'result error';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `<strong>❌ Error:</strong> ${error.message}`;
            }
        });
        </script>
    </body>
    </html>
    <?php
}

// ======================== ROUTING ========================

// Deteksi apakah ini request API atau web
if (php_sapi_name() === 'cli') {
    // Running dari command line
    testWeb2Apk();
} elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    // Request JSON API
    $service = new Web2ApkService();
    $service->handleRequest();
} else {
    // Tampilkan form HTML
    showForm();
}
?>
