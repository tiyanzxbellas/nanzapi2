<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Remove Background (DreamAI.art)
// Contoh: {"file": "foto.jpg"} atau {"url": "https://example.com/foto.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param url URL gambar (opsional kalau pakai file)

set_time_limit(120);

$credit = 'Tiyanz';
$imageUrl = $_GET['url'] ?? '';
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

if (empty($imageUrl) && !$hasFile) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter url atau file wajib diisi']);
    exit;
}

function randomDeviceId() { return bin2hex(random_bytes(8)); }

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function uploadToUploadEe($fileData, $fileName, $fileMime) {
    $cookieFile = sys_get_temp_dir() . '/uplee_' . uniqid() . '.txt';
    
    // Init session
    $ch = curl_init('https://www.upload.ee/?');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_exec($ch);
    curl_close($ch);
    
    // Get upload ID
    $rnd = time() * 1000;
    $ch = curl_init('https://www.upload.ee/ubr_link_upload.php?rnd_id=' . $rnd);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
    $page = curl_exec($ch);
    curl_close($ch);
    
    preg_match('/startUpload\("([^"]+)"/', $page, $m);
    $uploadId = $m[1] ?? '';
    if (!$uploadId) { @unlink($cookieFile); return ''; }
    
    // Upload
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"upfile_0\"; filename=\"{$fileName}\"\r\n";
    $body .= "Content-Type: {$fileMime}\r\n\r\n";
    $body .= $fileData . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"link\"\r\n\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"email\"\r\n\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"category\"\r\n\r\ncat_picture\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"big_resize\"\r\n\r\nnone\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"small_resize\"\r\n\r\n120x90\r\n";
    $body .= "--{$boundary}--";
    
    $uploadUrl = 'https://www.upload.ee/cgi-bin/ubr_upload.pl?X-Progress-ID=' . $uploadId . '&upload_id=' . $uploadId;
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'User-Agent: Mozilla/5.0'
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // Get result
    $ch = curl_init('https://www.upload.ee/?page=finished&upload_id=' . $uploadId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    $result = curl_exec($ch);
    curl_close($ch);
    
    @unlink($cookieFile);
    
    preg_match('/View file:\s*<br\s*\/?>\s*<a href=["\']?([^"\'>\s]+)["\']?/i', $result, $vm);
    $viewUrl = $vm[1] ?? '';
    
    // Convert ke direct image URL
    if ($viewUrl) {
        $viewUrl = str_replace('/files/', '/image/', $viewUrl);
        $viewUrl = preg_replace('/\.html$/', '', $viewUrl);
    }
    
    return $viewUrl;
}

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

try {
    // Upload ke Upload.ee
    if ($hasFile) {
        $fileData = file_get_contents($_FILES['file']['tmp_name']);
        $cdnUrl = uploadToUploadEe($fileData, $_FILES['file']['name'], mime_content_type($_FILES['file']['tmp_name']));
    } else {
        $imgData = curlGet($imageUrl);
        $cdnUrl = uploadToUploadEe($imgData, 'image.jpg', 'image/jpeg');
    }
    
    if (empty($cdnUrl)) throw new Exception('Upload gagal');
    
    // Remove background
    $result = curlPost('https://www.dreamai.art/api/background-remover', [
        'images' => [$cdnUrl]
    ], [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36',
        'Origin: https://www.dreamai.art',
        'Referer: https://www.dreamai.art/en/ai-background-remover',
        'X-Device-Id: ' . randomDeviceId()
    ]);
    
    if (!($result['success'] ?? false)) throw new Exception('Remove background gagal');
    
    $img = curlGet($result['processedImage'] ?? '');
    
    header('Content-Type: image/png');
    header('X-Creator: ' . $credit);
    echo $img;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => $e->getMessage()]);
}
?>