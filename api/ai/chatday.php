<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Chatday AI (1 req/detik) 
// Contoh: {"prompt":"Hai perkenalkan dirimu","model":"openai/gpt-5.5"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param prompt Pertanyaan untuk AI
// @param model (openai/gpt-5.5|openai/gpt-5.4|openai/gpt-5.3-chat|openai/gpt-5.1-instant|openai/gpt-5|openai/gpt-4o|openai/gpt-4o-mini|xai/grok-4.1-fast-non-reasoning|anthropic/claude-haiku-4.5|anthropic/claude-sonnet-4.6|anthropic/claude-opus-4.5|anthropic/claude-opus-4.6|anthropic/claude-opus-4.7|anthropic/claude-opus-4.8|anthropic/claude-fable-5|deepseek/deepseek-v4-pro|deepseek/deepseek-v4-flash|deepseek/deepseek-v3.2-thinking|google/gemini-3.1-pro-preview|google/gemini-3-pro-preview|google/gemini-3.1-flash-lite|alibaba/qwen3-max|meta/llama-4-maverick|moonshotai/kimi-k2.6) Model AI

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

// ========== RATE LIMIT ==========
$ip = $_SERVER['REMOTE_ADDR'];
$rateDir = __DIR__ . '/cache/ratelimit';

if (!is_dir($rateDir)) {
    mkdir($rateDir, 0777, true);
}

$rateFile = $rateDir . '/' . md5($ip) . '.json';
$now = microtime(true);

$rate = [
    'last_request' => 0,
    'ban_until' => 0
];

if (file_exists($rateFile)) {
    $json = json_decode(file_get_contents($rateFile), true);
    if (is_array($json)) {
        $rate = array_merge($rate, $json);
    }
}

if ($rate['ban_until'] > $now) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'result' => 'IP diblokir sementara, coba lagi dalam ' . ceil($rate['ban_until'] - $now) . ' detik'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($now - $rate['last_request']) < 1) {
    $rate['ban_until'] = $now + 60;
    file_put_contents($rateFile, json_encode($rate));

    echo json_encode(array_merge($credit, [
        'status' => false,
        'result' => 'Rate limit terlampaui, IP diblokir selama 1 menit'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$rate['last_request'] = $now;
$rate['ban_until'] = 0;

file_put_contents($rateFile, json_encode($rate));

// ============================
// CHATDAY AI
// ============================

$prompt = $_GET['prompt'] ?? '';
$model = $_GET['model'] ?? 'openai/gpt-5.5';

if (empty($prompt)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'result' => 'Parameter prompt wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$base_url = 'https://www.chatday.ai';

$baseHeaders = [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
    'Origin: ' . $base_url,
    'Referer: ' . $base_url . '/chat',
    'Content-Type: application/json'
];

// Anonymous Sign In
$responseHeaders = [];

$ch = curl_init($base_url . '/api/auth/sign-in/anonymous');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '{}',
    CURLOPT_HTTPHEADER => $baseHeaders,
    CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$responseHeaders) {
        $len = strlen($header);
        $parts = explode(':', $header, 2);
        if (count($parts) == 2) {
            $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
        }
        return $len;
    }
]);

$login = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'result' => curl_error($ch)
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

curl_close($ch);

$cookies = [];

if (isset($responseHeaders['set-cookie'])) {
    foreach ($responseHeaders['set-cookie'] as $cookie) {
        $cookies[] = explode(';', $cookie)[0];
    }
}

$cookie = implode('; ', $cookies);

$visitorId = str_replace('-', '', uuidv4());
$conversationId = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));

$payload = json_encode([
    'content' => $prompt,
    'model' => $model,
    'visitorId' => $visitorId,
    'conversationId' => $conversationId
]);

$ch = curl_init($base_url . '/api/v2/chat/anonymous');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
        'Origin: ' . $base_url,
        'Referer: ' . $base_url . '/chat',
        'Content-Type: application/json',
        'Cookie: ' . $cookie,
        'Accept: text/event-stream'
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'result' => curl_error($ch)
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

curl_close($ch);

$answer = '';

foreach (explode("\n", $response) as $line) {
    if (strpos($line, 'data:') !== 0) continue;

    $json = trim(substr($line, 5));
    if (!$json) continue;

    $evt = json_decode($json, true);

    if (
        isset($evt['type']) &&
        $evt['type'] === 'text-delta' &&
        isset($evt['delta'])
    ) {
        $answer .= $evt['delta'];
    }
}

$data = removeKeysRecursive([
    'model' => $model,
    'response' => $answer
], [
    'creator',
    'Creator',
    'author',
    'Author'
]);

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => $data
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>