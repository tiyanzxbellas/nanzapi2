<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Voice.ai Text-to-Speech
// Contoh: {"text": "Hai cuy, Nanas di sini", "voice": 2}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param voice (0|1|2|3|4|5|6|7|8|9|10) Voice ID

set_time_limit(30);

$credit = 'Tiyanz';
$text = $_GET['text'] ?? '';
$voiceId = intval($_GET['voice'] ?? 2);

if ($voiceId < 0 || $voiceId > 10) $voiceId = 2;

if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter text wajib diisi']);
    exit;
}

$xsrfToken = 'eyJpdiI6ImRGNE91Sm5SeUpPRS9kbm03K05xaFE9PSIsInZhbHVlIjoiWmtTMXBZaUVwZFdPTFllek5BR0E4M1RVZXdVcVh0QTZOQytweDMwaFVQZzRwbWpjZU9nNkVtNmlsWlZoVTYwMVV2RHkvYmtlQWluN21mWWxVMEZ6SjE4SDEra0pJbGVlcExEaHNKMnFRdlo0KzVaTXBXM1k5SXROWGNEVTgwOHkiLCJtYWMiOiI5YTMyZjZlNjRjNzU5NDNkMTEzODc0MWVhNDkwMTE2MWZkNmYwOWE4ZTE2NGUyNjg0MGViYWEyOTJhOGFmNGIwIiwidGFnIjoiIn0=';

$response = curlPost('https://voice.ai/api/tts/generate', [
    'voice_id' => $voiceId,
    'demo_text' => $text
], [
    'Content-Type: application/json',
    'Accept: audio/mpeg',
    'X-XSRF-TOKEN: ' . $xsrfToken
]);

if (empty($response)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'TTS gagal']);
    exit;
}

header('Content-Type: audio/mpeg');
header('X-Creator: ' . $credit);
echo $response;

function curlPost($url, $body, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch); curl_close($ch);
    return $res;
}
?>