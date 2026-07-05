<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: TikTok Downloader (SSSTik.io)
// Contoh: {"url": "https://vt.tiktok.com/ZSHfmQhYe/"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

$url = $_GET['url'] ?? '';

if (empty($url)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter url wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function generateTT() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 0; $i < 8; $i++) $result .= $chars[random_int(0, strlen($chars) - 1)];
    return $result;
}

function extractData($html) {
    $data = [];
    
    // Video tanpa watermark
    preg_match('/href="(https:\/\/tikcdn\.io\/ssstik\/\d+[^"]+)"\s+class="[^"]*without_watermark[^"]*vignette_active[^"]*"/', $html, $noWmMatch);
    $data['video_tanpa_watermark'] = $noWmMatch[1] ?? null;
    
    // Audio MP3
    preg_match('/href="(https:\/\/tikcdn\.io\/ssstik\/m\/[^"]+)"\s+class="[^"]*music[^"]*"/', $html, $mp3Match);
    $data['audio_mp3'] = $mp3Match[1] ?? null;
    
    // Caption
    preg_match('/<p class="maintext">([^<]+)<\/p>/', $html, $captionMatch);
    $data['caption'] = $captionMatch[1] ?? null;
    
    // Author
    preg_match('/<h2>([^<]+)<\/h2>/', $html, $authorMatch);
    $data['author'] = $authorMatch[1] ?? null;
    
    return $data;
}

try {
    $tt = generateTT();
    
    $postData = http_build_query([
        'id' => $url,
        'locale' => 'en',
        'tt' => $tt
    ]);
    
    $ch = curl_init('https://ssstik.io/abc?url=dl');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($postData),
        'HX-Request: true',
        'HX-Trigger: _gcaptcha_pt',
        'HX-Target: target',
        'HX-Current-URL: https://ssstik.io/en',
        'User-Agent: Mozilla/5.0 (Linux; Android 11; Termux) AppleWebKit/537.36',
        'Referer: https://ssstik.io/en'
    ]);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($html)) {
        throw new Exception('Scrape gagal (HTTP ' . $http_code . ')');
    }
    
    $result = extractData($html);
    
    if (empty($result['video_tanpa_watermark'])) {
        throw new Exception('Gagal mendapatkan link download');
    }
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'result' => $result
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>