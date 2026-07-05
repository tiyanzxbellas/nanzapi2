<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Image to Prompt AI - Generate prompt dari gambar
// Contoh: {"file": "pilih_gambar.jpg"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(90);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'File wajib diupload (POST multipart/form-data)'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$filePath = $file['tmp_name'];
$fileName = $file['name'];

define('BASE_URL', 'https://aiconvert.online/api');
define('UA', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36');

function getMimeType($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp',
        'gif' => 'image/gif', 'bmp' => 'image/bmp'
    ];
    return $mimeTypes[$ext] ?? 'image/jpeg';
}

try {
    $base64 = base64_encode(file_get_contents($filePath));
    $mimeType = getMimeType($fileName);
    
    // Step 1: Submit image
    $payload = json_encode([
        'imageData' => $base64,
        'mimeType' => $mimeType,
        'language' => 'en',
        'promptType' => 'concise'
    ]);
    
    $ch = curl_init(BASE_URL . '/submit-prompt-job');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: ' . UA,
            'Referer: https://aiconvert.online/prompt-generator'
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $submitResult = json_decode($response, true);
    
    if (empty($submitResult['taskId'])) {
        throw new Exception($submitResult['message'] ?? 'Gagal submit gambar');
    }
    
    $taskId = $submitResult['taskId'];
    
    // Step 2: Polling status
    $maxRetry = 25;
    $success = false;
    $prompt = null;
    
    for ($i = 0; $i < $maxRetry; $i++) {
        sleep(2);
        
        $ch = curl_init(BASE_URL . '/check-status-kv?taskId=' . $taskId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: ' . UA,
                'Referer: https://aiconvert.online/prompt-generator'
            ]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $statusResult = json_decode($response, true);
        
        if (($statusResult['status'] ?? '') === 'SUCCESS' && !empty($statusResult['result']['generatedPrompt'])) {
            $success = true;
            $prompt = $statusResult['result']['generatedPrompt'];
            break;
        }
    }
    
    if ($success) {
        echo json_encode(array_merge($credit, [
            'status' => true,
            'task_id' => $taskId,
            'input' => $fileName,
            'result' => $prompt
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Waktu habis, prompt tidak kunjung selesai');
    }
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>