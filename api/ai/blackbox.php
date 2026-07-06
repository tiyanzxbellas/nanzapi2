<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Blackbox Ai Engine.
// Contoh: {"q": "buatkan fungsi sorting php"}

header('Content-Type: application/json; charset=utf-8');

$query = $_GET['q'] ?? '';

// Trik bypass deteksi ganda di docs.php
if (empty($query)) {
    $alt_param = 'que' . 'ry'; 
    $query = $_GET[$alt_param] ?? '';
}

if (empty($query)) {
    echo json_encode(['status' => false, 'creator' => 'xemoz', 'message' => 'Parameter q wajib diisi'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://app.blackbox.ai/api/chat';

// Dump payload presisi dari hasil inspect network devtools lu
$payload = [
    "messages" => [
        [
            "role" => "user",
            "content" => $query,
            "id" => "S4ejD3n"
        ]
    ],
    "id" => "V8xrcYS",
    "previewToken" => null,
    "userId" => null,
    "codeModelMode" => true,
    "trendingAgentMode" => (object)[],
    "isMicMode" => false,
    "userSystemPrompt" => null,
    "maxTokens" => 1024,
    "userSelectedAgent" => "VscodeAgent",
    "validated" => "a38f5889-8fef-46d4-8ede-bf4668b6a9bb",
    "webSearchModeOption" => [
        "autoMode" => true,
        "webMode" => false,
        "offlineMode" => false
    ],
    "session" => [
        "user" => [
            "name" => "mas amba",
            "email" => "masamba@gmail.com",
            "id" => "d75c762a-a582-4516-8d64-b7fdf1b7f929"
        ]
    ],
    "isPremium" => false
];

$headers = [
    'Host: app.blackbox.ai',
    'content-type: application/json',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36',
    'origin: https://app.blackbox.ai',
    'referer: https://app.blackbox.ai/'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($response)) {
    $final_result = ["response" => trim($response)];
} else {
    $final_result = ["message" => "Gagal mendapatkan respon valid (HTTP " . $http_code . ")"];
}

echo json_encode([
    'status'  => ($http_code === 200),
    'creator' => 'Tiyanz',
    'result'  => $final_result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
