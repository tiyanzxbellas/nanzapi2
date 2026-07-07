<?php
/*
 * [ Catbox.moe ]
 * creator : Tiyanz
 * base    : https://catbox.moe
 * channel : https://whatsapp.com/channel/0029VbCpRwY2ZjChYNTEFj1G
 * support : me with follow my channel
 */

error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Catbox.moe Uploader (Node.js style)
// Contoh: {"file": "pilih_file.jpg"}

header('Content-Type: application/json; charset=utf-8');

// Fungsi uploadCatbox (mirip versi Node.js)
function uploadCatbox($filePath) {
    try {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        
        if ($fileSize > 200 * 1024 * 1024) {
            return ['success' => false, 'error' => 'File terlalu besar (max 200MB)'];
        }

        // ========== BUAT MULTIPART MANUAL ==========
        $boundary = md5(time());
        $body = "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"reqtype\"\r\n\r\n";
        $body .= "fileupload\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"userhash\"\r\n\r\n";
        $body .= "\r\n"; // kosong untuk anonymous
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"fileToUpload\"; filename=\"" . $fileName . "\"\r\n";
        $body .= "Content-Type: " . mime_content_type($filePath) . "\r\n\r\n";
        $body .= file_get_contents($filePath) . "\r\n";
        $body .= "--$boundary--\r\n";

        $ch = curl_init('https://catbox.moe/user/api.php');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 menit (mirip timeout Node.js)
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.9',
            'Origin: https://catbox.moe',
            'Referer: https://catbox.moe/'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'CURL Error: ' . $error];
        }

        if ($http_code !== 200) {
            return ['success' => false, 'error' => 'HTTP ' . $http_code . ': ' . $response];
        }

        $data = trim($response);
        
        // Validasi (mirip versi Node.js)
        if ($data && strpos($data, 'https://files.catbox.moe/') === 0) {
            return ['success' => true, 'url' => $data];
        } else {
            return ['success' => false, 'error' => $data];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ========== HANDLE REQUEST ==========
try {
    // Cek apakah ada file diupload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File wajib diupload');
    }

    $file = $_FILES['file'];
    $fileTmp = $file['tmp_name'];
    
    // Panggil fungsi uploadCatbox
    $result = uploadCatbox($fileTmp);
    
    if ($result['success']) {
        echo json_encode([
            'creator' => 'Tiyanz',
            'status' => true,
            'result' => [
                'url' => $result['url'],
                'filename' => $file['name'],
                'size' => $file['size']
            ]
        ]);
    } else {
        throw new Exception($result['error']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>
