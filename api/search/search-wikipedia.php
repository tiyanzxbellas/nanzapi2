<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Wikipedia search 
// Contoh: {"q": "Apa Itu Free Fire"
header("Content-Type: application/json; charset=utf-8");

function requestApi($url, $successMessage, $errorMessage) {
    if (empty($url)) {
        echo json_encode(["status" => false, "message" => $errorMessage, "creator" => "Tiyanz", "timestamp" => time(), "result" => null], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $isSuccess = ($httpCode === 200 && $result);
    echo json_encode(["status" => $isSuccess, "message" => $isSuccess ? $successMessage : $errorMessage, "creator" => "Tiyanz", "timestamp" => time(), "result" => $isSuccess ? json_decode($result, true) : null], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (empty($q)) requestApi('', '', 'Parameter &q= tidak boleh kosong');
requestApi('https://api.lexcode.biz.id/api/search/wikipedia?q=' . urlencode($q), 'Berhasil mencari di wikipedia', 'Gagal mencari di wikipedia');
