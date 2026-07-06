<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: UnlimitedAI Chat Scraper
// Contoh: {"prompt": Halo"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param model (chat-model-reasoning|chat-model-standard) Pilih model AI

header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

$prompt = $_GET['prompt'] ?? '';
$model = $_GET['model'] ?? 'chat-model-reasoning';

$allowedModels = ['chat-model-reasoning', 'chat-model-standard'];
if (!in_array($model, $allowedModels)) {
    $model = 'chat-model-reasoning';
}

if (empty($prompt)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Parameter prompt wajib diisi']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$chatId = generateUUID();
$messageId = generateUUID();
$assistantId = generateUUID();
$deviceId = generateUUID();
$locale = 'id';
$timestamp = date('c');

$payload = json_encode([
    'chatId' => $chatId,
    'messages' => [
        [
            'id' => $messageId,
            'role' => 'user',
            'content' => $prompt,
            'parts' => [['type' => 'text', 'text' => $prompt]],
            'createdAt' => $timestamp
        ],
        [
            'id' => $assistantId,
            'role' => 'assistant',
            'content' => '',
            'parts' => [['type' => 'text', 'text' => '']],
            'createdAt' => $timestamp
        ]
    ],
    'selectedChatModel' => $model,
    'selectedCharacter' => null,
    'selectedStory' => null,
    'deviceId' => $deviceId,
    'locale' => $locale
]);

$ch = curl_init('https://app.unlimitedai.chat/api/chat');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-next-intl-locale: ' . $locale,
    'Content-Length: ' . strlen($payload),
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => $error]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($http_code !== 200 || empty($response)) {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'API request gagal (HTTP ' . $http_code . ')']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$lines = array_filter(explode("\n", trim($response)));
$fullText = '';
$rawChunks = [];

foreach ($lines as $line) {
    $parsed = json_decode($line, true);
    if ($parsed) {
        $rawChunks[] = $parsed;
        if (($parsed['type'] ?? '') === 'delta' && isset($parsed['delta'])) {
            $fullText .= $parsed['delta'];
        }
    }
}

echo json_encode(array_merge(
    $credit,
    [
        'status' => true,
        'result' => [
            'text' => $fullText,
            'totalChars' => strlen($fullText),
            'chatId' => $chatId,
            'deviceId' => $deviceId,
            'model' => $model,
            'locale' => $locale
        ]
    ]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>