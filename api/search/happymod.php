<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fitur Happymod Search Premium via Nanzz Engine.
// Contoh: {"q": "subway surfers"}

header('Content-Type: application/json; charset=utf-8');

// Ambil data dari parameter 'q' utama agar dashboard interaktif lu rapi
$query = $_GET['q'] ?? '';

// Trik bypass string query agar docs.php tidak memunculkan kolom ganda
if (empty($query)) {
    $alt_param = 'que' . 'ry'; 
    $query = $_GET[$alt_param] ?? '';
}

if (empty($query)) {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'message' => 'Parameter q wajib diisi'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$target_param = 'q';
$api_url = "https://api.lexcode.biz.id/api/search/happymod?" . $target_param . "=" . urlencode($query);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 35);
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
        $msg_err = $data['message'] ?? ($data['msg'] ?? 'Search Failed');
        $final_result = ['message' => trim(str_ireplace(['lexcode', '❌'], '', $msg_err))];
    } else {
        $final_result = $data['result'] ?? ($data['data'] ?? $data);
    }
} else {
    $final_status = false;
    $final_result = ['message' => 'Gagal terhubung ke server hulu (HTTP ' . $http_code . ')'];
}

function clean_Nanzz_watermark(&$item) {
    if (is_array($item)) {
        unset($item['creator'], $item['author'], $item['attribution'], $item['code']);
        foreach ($item as &$value) { if (is_array($value)) { clean_Nanzz_watermark($value); } }
    }
}
clean_Nanzz_watermark($final_result);

echo json_encode([
    'status'  => $final_status,
    'creator' => 'Nanzz',
    'result'  => $final_result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
