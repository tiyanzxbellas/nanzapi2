<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: ChatGPT Free AI (Multi Model)
// Contoh: {"text": "Halo apa kabar?"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param model (chatgpt|deepseek|claude|grok|perplexity|llama|qwen) Pilih model AI

header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];

$text = $_GET['text'] ?? '';
$model = $_GET['model'] ?? 'chatgpt';

$models = [
    'chatgpt'    => ['bot_id' => 25871, 'name' => 'ChatGPT 5 Nano'],
    'deepseek'   => ['bot_id' => 25873, 'name' => 'DeepSeek'],
    'claude'     => ['bot_id' => 25875, 'name' => 'Claude'],
    'grok'       => ['bot_id' => 25872, 'name' => 'Xai (Grok)'],
    'perplexity' => ['bot_id' => 29624, 'name' => 'Perplexity Sonar'],
    'llama'      => ['bot_id' => 25870, 'name' => 'Meta: Llama 4 Maverick'],
    'qwen'       => ['bot_id' => 25869, 'name' => 'Qwen 3 30B A3B'],
];

if (empty($text)) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Parameter text wajib diisi']), JSON_PRETTY_PRINT);
    exit;
}

if (!isset($models[$model])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Model tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

define('BASE', 'https://chatgptfree.ai');
define('AJAX', BASE . '/wp-admin/admin-ajax.php');
define('UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');

function curlPost($url, $data, $cookieFile = null, $extraHeaders = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $headers = array_merge([
        'User-Agent: ' . UA,
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.9',
        'Referer: ' . BASE . '/chat/',
        'Origin: ' . BASE,
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest'
    ], $extraHeaders);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($cookieFile) { curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); }
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

function curlGet($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: text/event-stream',
        'Referer: ' . BASE . '/chat/',
        'Origin: ' . BASE,
        'X-Requested-With: XMLHttpRequest'
    ]);
    if ($cookieFile) { curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

try {
    $cfg = $models[$model];
    $ck = sys_get_temp_dir() . '/gptfree_' . uniqid() . '.txt';
    
    // Init session
    $ch = curl_init(BASE . '/chat/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $ck);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . UA,
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9'
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // Nonce
    $nonceRes = curlPost(AJAX, [
        'action' => 'aipkit_get_frontend_chat_nonce',
        'bot_id' => '25871',
        'post_id' => '261'
    ], $ck);
    
    $nonceData = json_decode($nonceRes['body'], true);
    $nonce = $nonceData['data']['nonce'] ?? '';
    
    if (!$nonce) throw new Exception('Gagal nonce: HTTP ' . $nonceRes['code'] . ' - ' . substr($nonceRes['body'], 0, 200));
    
    $suid = uuid(); $cuid = uuid(); $mid = uuid(); $ckey = uuid(); $ts = strval(time()*1000);
    
    // Cache
    $cacheRes = curlPost(AJAX, [
        'action' => 'aipkit_cache_sse_message',
        'bot_id' => $cfg['bot_id'],
        'message' => $text,
        '_ajax_nonce' => $nonce,
        'post_id' => '261',
        'user_client_message_id' => $mid,
        'cache_key' => $ckey,
        'session_id' => $suid,
        'conversation_uuid' => $cuid,
        '_ts' => $ts
    ], $ck);
    
    $cacheData = json_decode($cacheRes['body'], true);
    if (!($cacheData['success'] ?? false)) throw new Exception('Cache failed: ' . $cacheRes['body']);
    
    // Stream
    $surl = AJAX . '?action=aipkit_frontend_chat_stream&cache_key=' . urlencode($cacheData['data']['cache_key']) . '&bot_id=' . $cfg['bot_id'] . '&session_id=' . $suid . '&conversation_uuid=' . $cuid . '&post_id=261&_ajax_nonce=' . urlencode($nonce) . '&_ts=' . $ts;
    $stream = curlGet($surl, $ck);
    @unlink($ck);
    
    // Parse SSE
    $fullText = '';
    foreach (explode("\n", $stream) as $line) {
        if (strpos($line, 'data: ') === 0) {
            $j = json_decode(substr($line, 6), true);
            if ($j && !empty($j['delta'])) $fullText .= $j['delta'];
            if (!empty($j['finished'])) break;
        }
    }
    
    if (!$fullText) throw new Exception('Response kosong');
    
    echo json_encode(array_merge($credit, ['status' => true, 'result' => ['model' => $cfg['name'], 'text' => $fullText]]), JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    @unlink($ck ?? '');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => $e->getMessage()]), JSON_PRETTY_PRINT);
}
?>