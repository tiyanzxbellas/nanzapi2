<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Crikk Text-to-Speech Indonesia
// Contoh: {"text": "HAHAHA TEST", "voice": "laki"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param voice (laki|perempuan) Jenis suara

set_time_limit(30);

$credit = 'Tiyanz';
$text = $_GET['text'] ?? '';
$voiceType = $_GET['voice'] ?? 'laki';

$voices = [
    'laki'      => 'id-ID-ArdiNeural',
    'perempuan' => 'id-ID-GadisNeural'
];

if (!isset($voices[$voiceType])) $voiceType = 'laki';
$voice = $voices[$voiceType];

if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode(['creator' => $credit, 'status' => false, 'message' => 'Parameter text wajib diisi']);
    exit;
}

$ch = curl_init('https://crikk.com/api/tts/guest');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'text' => $text,
    'voice' => $voice
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: audio/mpeg, application/json',
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
    'Origin: https://crikk.com',
    'Referer: https://crikk.com/'
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => $credit,
        'status' => false,
        'message' => 'TTS gagal (HTTP ' . $http_code . ')',
        'debug' => [
            'content_type' => $contentType,
            'response_preview' => substr($response, 0, 200)
        ]
    ]);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'audio/mpeg'));
header('X-Creator: ' . $credit);
echo $response;
?>