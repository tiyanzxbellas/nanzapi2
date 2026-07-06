<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: AI Prompt Generator 
// Contoh: {"text":"king biologi"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Deskripsi

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];
set_time_limit(60);

$text = $_GET['text'] ?? 'king biologi';

if (!$text) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter text diperlukan'
    ]), JSON_PRETTY_PRINT);
    exit;
}

$result = ['english' => '', 'indonesian' => ''];

for ($i = 0; $i < 3; $i++) {
    $ch = curl_init('https://generateprompt-faddai.vercel.app/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['prompt' => $text, 'mode' => 'stream']),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: Mozilla/5.0']
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) continue;
    
    foreach (explode("\n", $response) as $line) {
        $line = trim($line);
        if (strpos($line, 'data: ') !== 0) continue;
        $data = json_decode(substr($line, 6), true);
        if (!$data) continue;
        if (!empty($data['english'])) $result['english'] = $data['english'];
        if (!empty($data['indonesian'])) $result['indonesian'] = $data['indonesian'];
    }
    
    if ($result['english'] || $result['indonesian']) break;
    usleep(500000);
}

echo json_encode(array_merge($credit, [
    'status' => !empty($result['english']),
    'input' => $text,
    'result' => $result
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>