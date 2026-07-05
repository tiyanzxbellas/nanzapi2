<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Video to Transcript (TikTok/Reels)
// Contoh: {"url": "https://vt.tiktok.com/ZSxEsydgv/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120);

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

$url = $_GET['url'] ?? '';

if (empty($url)) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter url wajib diisi']), JSON_PRETTY_PRINT);
    exit;
}

define('ASSEMBLYAI_KEY', 'b6d6101e7ded44a6921bc5a8146765a1');

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

function curlGet($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

try {
    // Step 1: Download video via Cobalt
    $cobalt = curlPost('https://cobalt-api-production-cd7d.up.railway.app/', ['url' => $url], ['Accept: application/json']);
    
    $audioUrl = $cobalt['url'] ?? '';
    if (empty($audioUrl)) throw new Exception('Gagal download video');
    
    // Step 2: Submit ke AssemblyAI
    $transcript = curlPost('https://api.assemblyai.com/v2/transcript', [
        'audio_url' => $audioUrl,
        'speaker_labels' => true,
        'language_code' => 'en_us'
    ], ['Authorization: ' . ASSEMBLYAI_KEY]);
    
    $taskId = $transcript['id'] ?? '';
    if (empty($taskId)) throw new Exception('Gagal submit transcript');
    
    // Step 3: Polling
    $result = null;
    for ($i = 0; $i < 30; $i++) {
        sleep(2);
        $result = curlGet('https://api.assemblyai.com/v2/transcript/' . $taskId, ['Authorization: ' . ASSEMBLYAI_KEY]);
        
        if ($result['status'] === 'completed') break;
        if ($result['status'] === 'error') throw new Exception('Transcript error');
    }
    
    if ($result['status'] !== 'completed') throw new Exception('Timeout');
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => [
            'text' => $result['text'] ?? '',
            'duration' => $result['audio_duration'] ?? 0,
            'language' => $result['language_code'] ?? '',
            'words' => $result['words'] ?? []
        ]
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => $e->getMessage()]), JSON_PRETTY_PRINT);
}
?>