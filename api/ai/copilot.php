<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Fitur Copilot AI Pro 
// Contoh: {"q": "halo"}

header('Content-Type: application/json; charset=utf-8');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    $alt_param = 'que' . 'ry'; 
    $query = $_GET[$alt_param] ?? '';
}

if (empty($query)) {
    echo json_encode(['status' => false, 'creator' => 'xemoz', 'message' => 'Parameter q wajib diisi'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$target_param = 'prompt';
$api_url = "https://api.lexcode.biz.id/api/ai/copilot?" . $target_param . "=" . urlencode($query);

$api_url .= "&model=gpt-5";

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
        $msg_err = $data['message'] ?? ($data['msg'] ?? 'Upstream Error');
        $final_result = ['message' => trim(str_ireplace(['lexcode', '❌'], '', $msg_err))];
    } else {
        $final_result = $data['result'] ?? ($data['data'] ?? $data);
    }
} else {
    if ($http_code === 200 && !empty($response)) {
        $final_result = ["response" => trim($response)];
    } else {
        $final_status = false;
        $final_result = ['message' => 'Gagal mendapatkan data valid dari server hulu (HTTP ' . $http_code . ')'];
    }
}

function clean_xemoz_watermark(&$item) {
    if (is_array($item)) {
        unset($item['creator'], $item['author'], $item['attribution'], $item['code']);
        foreach ($item as &$value) { if (is_array($value)) { clean_xemoz_watermark($value); } }
    }
}
clean_xemoz_watermark($final_result);

echo json_encode([
    'status'  => $final_status,
    'creator' => 'Nanzz',
    'result'  => $final_result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>