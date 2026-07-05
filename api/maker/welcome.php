<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Fake Welcome Member Card V3
// Contoh: POST multipart/form-data: file=pp.jpg, file=background.jpg, nama=nanas, namagrup=nanas, memberke=1
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Nama User
// @param namagrup Nama Grup
// @param memberke Nomor Member

header('Content-Type: image/png; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$nama     = trim($_POST['nama'] ?? 'nanasnanas');
$namagrup = trim($_POST['namagrup'] ?? 'nanas');
$memberke = trim($_POST['memberke'] ?? '1');
$files    = $_FILES['file'] ?? [];

$data = [
    'status' => false,
    'creator' => 'Nanzz',
    'input' => ['nama' => $nama, 'namagrup' => $namagrup, 'memberke' => $memberke],
    'result' => null
];

// Normalize files
$uploadedFiles = [];
if (isset($files['tmp_name'])) {
    if (is_array($files['tmp_name'])) {
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] === 0) {
                $uploadedFiles[] = [
                    'tmp_name' => $tmp,
                    'type' => $files['type'][$i],
                    'name' => $files['name'][$i],
                ];
            }
        }
    } else {
        if ($files['error'] === 0) {
            $uploadedFiles[] = [
                'tmp_name' => $files['tmp_name'],
                'type' => $files['type'],
                'name' => $files['name'],
            ];
        }
    }
}

if (empty($uploadedFiles)) {
    $data['result'] = ['msg' => 'File wajib diupload (file pertama = pp, file kedua = background opsional)'];
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$postFields = [
    'avatar' => new CURLFile($uploadedFiles[0]['tmp_name'], $uploadedFiles[0]['type'], $uploadedFiles[0]['name']),
    'username' => $nama,
    'guildName' => $namagrup,
    'memberCount' => $memberke,
];

// File kedua = background (opsional)
if (isset($uploadedFiles[1])) {
    $postFields['background'] = new CURLFile($uploadedFiles[1]['tmp_name'], $uploadedFiles[1]['type'], $uploadedFiles[1]['name']);
}

$ch = curl_init('https://api.theresav.biz.id/canvas/welcomev3');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_POSTFIELDS => $postFields,
]);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($imageData)) {
    $data['result'] = ['msg' => 'Gagal generate', 'http_code' => $httpCode];
    $keysToRemove = ['creator', 'Creator', 'author', 'Author'];
    $data = removeKeysRecursive($data, $keysToRemove);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: image/png');
echo $imageData;
exit;

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>