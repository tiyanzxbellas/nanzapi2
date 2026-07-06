<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fitur Twitter Downloader Premium via Tiyanz Downloader.
// Contoh: {"q": "https://x.com/user/status/123"}

header('Content-Type: application/json; charset=utf-8');

// Gunakan parameter tunggal q agar playground docs.php tampil rapi dan konsisten
$query = $_GET['q'] ?? '';

// Trik bypass string penangkap agar dashboard tidak membuat double-column input
if (empty($query)) {
    $alt_param = 'que' . 'ry'; 
    $query = $_GET[$alt_param] ?? '';
}

if (empty($query)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => 'Parameter q wajib diisi'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$target_param = 'url';
$api_url = "https://api.lexcode.biz.id/api/dwn/twitter?" . $target_param . "=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 40);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$final_status = true;
$final_result = null;

if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
    if (isset($data['status']) && $data['status'] === false) {
        $final_status = false;
        $msg_err = $data['message'] ?? ($data['msg'] ?? 'Gagal memproses link unduhan');
        $final_result = ['message' => trim(str_ireplace(['lexcode', '❌'], '', $msg_err))];
    } else {
        $final_result = $data['result'] ?? ($data['data'] ?? $data);
    }
} else {
    $final_status = false;
    $final_result = ['message' => 'Terjadi kesalahan respon dari server hulu (HTTP ' . $http_code . ')'];
}

function clean_Tiyanz_watermark(&$item) {
    if (is_array($item)) {
        unset($item['creator'], $item['author'], $item['attribution'], $item['code']);
        foreach ($item as &$value) { if (is_array($value)) { clean_Tiyanz_watermark($value); } }
    }
}
clean_Tiyanz_watermark($final_result);

echo json_encode([
    'status'  => $final_status,
    'creator' => 'Tiyanz',
    'result'  => $final_result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>