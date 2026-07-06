<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Gemini AI Chat
// Contoh: {"text":"Hello"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param text Pertanyaan

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];
set_time_limit(120);

$text = $_GET['text'] ?? 'Hello';

if (!$text) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter text diperlukan']), JSON_PRETTY_PRINT);
    exit;
}

// Generate tokens
function randStr($len) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    $str = '';
    for ($i = 0; $i < $len; $i++) $str .= $chars[rand(0, strlen($chars) - 1)];
    return $str;
}

$deviceId = bin2hex(random_bytes(16));
$fSid = '-' . rand(1000000000000000000, 9999999999999999999);
$atToken = 'AOOh' . randStr(22) . ':' . (time() * 1000000);
$sessionId = bin2hex(random_bytes(16));
$tokenBlob = '!' . randStr(24) . 'NAAa-PB6hnjxC' . randStr(18) . 'AEABE' . randStr(12) .
    'Z1IzrYRasYCYYnM4bZXAlvfpPcJe2g2Ye8XDL3Ck5BCikk5IYm5xZrnIsIkA0SEgfgSLBh-eSq-mq5McSAgAA' .
    randStr(8) . 'SAAAC' . randStr(8) . 'BB34ARK' . randStr(1100);

$reqId = rand(1000000, 9999999);

// Build payload
$inner = [
    [$text, 0, null, null, null, null, 0],
    ['id'],
    ['', '', '', null, null, null, null, null, null, ''],
    $tokenBlob,
    $sessionId,
    null,
    [0],
    1, null, null, 1, 0, null, null, null, null, null, [[0]], 0,
    null, null, null, null, null, null, null, null, 1, null, null, [4],
    null, null, null, null, null, null, null, null, null, null, [2],
    null, null, null, null, null, null, null, null, null, null, null, 0,
    null, null, null, null, null, $deviceId, null, [], null, null, null, null, null, null, 1,
    null, null, null, null, null, null, null, null, null, null, 1
];

$outer = [null, json_encode($inner)];
$body = 'f.req=' . urlencode(json_encode($outer)) . '&at=' . urlencode($atToken);

$url = 'https://gemini.google.com/_/BardChatUi/data/assistant.lamda.BardFrontendService/StreamGenerate';
$url .= '?bl=boq_assistant-bard-web-server_20260603.11_p0';
$url .= '&f.sid=' . $fSid;
$url .= '&hl=id';
$url .= '&_reqid=' . $reqId;
$url .= '&rt=c';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
        'Origin: https://gemini.google.com',
        'Referer: https://gemini.google.com/',
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
        'sec-ch-ua-platform: "Android"',
        'x-same-domain: 1',
        'x-goog-ext-525001261-jspb: [1,null,null,null,"fbb127bbb056c959",null,null,0,[4],null,null,1,null,null,1,null,"' . $deviceId . '"]',
        'x-goog-ext-525005358-jspb: ["' . $deviceId . '",1]'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Parse response
$textResult = '';
foreach (explode("\n", $response) as $line) {
    $line = trim($line);
    if (strpos($line, ")]}'") === 0) $line = substr($line, 4);
    if (!$line || is_numeric($line)) continue;
    
    $parsed = json_decode($line, true);
    if (!$parsed || !$parsed[0]) continue;
    
    $encoded = $parsed[0][2] ?? null;
    if (!$encoded) continue;
    
    $inner = json_decode($encoded, true);
    $messages = $inner[4] ?? [];
    
    foreach ($messages as $block) {
        if (is_array($block[1]) && !empty($block[1][0])) {
            $textResult = $block[1][0];
        }
    }
}

echo json_encode(array_merge($credit, [
    'status' => !empty($textResult),
    'input' => $text,
    'result' => $textResult ?: 'Gagal mendapatkan response'
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>