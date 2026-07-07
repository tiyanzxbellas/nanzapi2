<?php
/**
 * Web2ApkService
 * Sistem inti untuk mengubah website jadi APK lewat webappcreator.amethystlab.org.
 * Murni logic — tidak tahu apa-apa soal bot, chat, atau platform pengirim pesan.
 * Bisa dipanggil dari command bot, API route, script CLI, dll.
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
     * Menyimpan buffer icon ke file temporary
     */
    public function saveIconBuffer($buffer)
    {
        $tempDir = sys_get_temp_dir() . '/web2apk';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $iconPath = $tempDir . '/icon_' . time() . '.png';
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
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('CURL Error: ' . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode);
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg());
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
                'downloadUrl' => $this->baseUrl . $data['downloadUrl']
            ];
        } finally {
            // Hapus file temporary
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }
    }
}

// Contoh penggunaan:
function contohStandalone()
{
    $service = new Web2ApkService();

    try {
        // Baca file icon
        $iconBuffer = file_get_contents('./get.jpg'); // ganti sesuai path icon-mu

        if ($iconBuffer === false) {
            throw new Exception('Gagal membaca file icon');
        }

        $result = $service->build([
            'url' => 'https://www.google.com',
            'appName' => 'Google App',
            'iconBuffer' => $iconBuffer,
            'versionName' => '1.0.0',
            'versionCode' => 1,
        ]);

        echo "Berhasil:\n";
        print_r($result);
        // Array: [success] => true, [appName] => Google App, [packageName] => com.googleapp.web2apk, [downloadUrl] => ...
    } catch (Exception $err) {
        echo "Gagal build APK: " . $err->getMessage() . "\n";
    }
}

// Jalankan contoh
contohStandalone();
?>
