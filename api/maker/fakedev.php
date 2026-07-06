<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Tiyanz API - FakeDev Card Wrapper
// Contoh: {"nama":"TiyanzCode","bio":"Have a Great Code","fotourl":"https://filegoat.s3.de.io.cloud.ovh.net/8342cda4-ec06-4c04-ae16-0fa16a30c369/file_0000000074d47208bec22b89425caf8b.png"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Nama / Judul Utama
// @param bio Username / Subtitle
// @param fotourl URL Foto Profil

header('Content-Type: image/png; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

$nama   = trim($_GET['nama'] ?? 'Tiyanz');
$bio    = trim($_GET['bio'] ?? '@TiyanzApi');
$fotourl = trim($_GET['fotourl'] ?? '');

if (empty($fotourl)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'msg' => 'fotourl diperlukan']);
    exit;
}

// Forward ke API asli
$url = 'https://api-tiyanz.vercel.app/maker/fakedev?' . http_build_query([
    'urlfoto' => $fotourl,
    'text1' => $nama,
    'text2' => $bio,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => ['Accept: image/png'],
]);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || empty($imageData)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'creator' => 'Tiyanz', 'msg' => 'Gagal fetch dari API asli', 'http_code' => $httpCode]);
    exit;
}

// Output gambar langsung
header('Content-Type: ' . ($contentType ?: 'image/png'));
echo $imageData;
?>