<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Quillbot AI Chat
// Contoh: {"text":"Halo bro gimana kabar mu"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Pertanyaan

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];
set_time_limit(60);

$text = $_GET['text'] ?? 'Halo bro gimana kabar mu';

if (!$text) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter text diperlukan']), JSON_PRETTY_PRINT);
    exit;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$cookieFile = sys_get_temp_dir() . '/quillbot_' . uniqid() . '.txt';
$conversationId = uuid();

function curlRequest($url, $cookieFile, $post = false, $data = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_HTTPHEADER => array_merge([
            'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
            'Accept: text/event-stream, application/json'
        ], $headers)
    ]);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode];
}

// Visit homepage
curlRequest('https://quillbot.com/', $cookieFile, false, null, [
    'Accept: text/html',
    'sec-ch-ua: "Google Chrome";v="147"',
    'sec-ch-ua-mobile: ?1',
    'sec-ch-ua-platform: "Android"'
]);

// Chat
$chat = curlRequest('https://quillbot.com/api/ai-chat/chat/conversation/' . $conversationId, $cookieFile, true, [
    'message' => ['content' => $text . "\n\n"],
    'context' => ['editorContext' => '', 'selectionContext' => '', 'userDialect' => 'en-us', 'apiVersion' => 2],
    'origin' => ['name' => 'ai-chat.chat', 'url' => 'https://quillbot.com']
], [
    'Content-Type: application/json',
    'Origin: https://quillbot.com',
    'Referer: https://quillbot.com/ai-chat/c/' . $conversationId,
    'webapp-version: 42.51.6',
    'qb-product: AI-CHAT',
    'platform-type: webapp'
]);

$result = '';
foreach (explode("\n", $chat['body']) as $line) {
    $line = trim($line);
    if (strpos($line, '{') === 0) {
        $json = json_decode($line, true);
        if ($json && ($json['type'] ?? '') === 'content') {
            $result .= $json['content'] ?? '';
        }
    }
}

@unlink($cookieFile);

echo json_encode(array_merge($credit, [
    'status' => $chat['code'] === 200 && !empty($result),
    'input' => $text,
    'result' => $result ?: 'Gagal'
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>