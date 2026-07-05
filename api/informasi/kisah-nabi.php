<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Kisah Nabi (25 Nabi)
// Contoh: {"nabi": "adam"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nabi (adam|idris|nuh|hud|shaleh|ibrahim|luth|ismail|ishaq|yaqub|yusuf|ayyub|syuaib|musa|harun|dzulkifli|daud|sulaiman|ilyas|ilyasa|yunus|zakaria|yahya|isa|muhammad) Pilih nama nabi

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz', 
    'author' => 'Nanzz'
];

$nabi = strtolower($_GET['nabi'] ?? '');

// Validasi dropdown
$allowedNabi = [
    'adam', 'idris', 'nuh', 'hud', 'shaleh', 'ibrahim', 'luth', 'ismail', 'ishaq',
    'yaqub', 'yusuf', 'ayyub', 'syuaib', 'musa', 'harun', 'dzulkifli', 'daud',
    'sulaiman', 'ilyas', 'ilyasa', 'yunus', 'zakaria', 'yahya', 'isa', 'muhammad'
];

if (empty($nabi) || !in_array($nabi, $allowedNabi)) {
    echo json_encode(array_merge(
        $credit,
        [
            'status' => false,
            'message' => 'Parameter nabi tidak valid',
            'options' => $allowedNabi
        ]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://raw.githubusercontent.com/ZeroChanBot/Api-Freee/a9da6483809a1fbf164cdf1dfbfc6a17f2814577/data/kisahNabi/' . $nabi . '.json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['name'])) {
        echo json_encode(array_merge(
            $credit,
            ['status' => false, 'message' => 'Data nabi tidak ditemukan']
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    echo json_encode(array_merge(
        $credit,
        [
            'status' => true,
            'result' => [
                'name' => $data['name'] ?? '',
                'lahir' => $data['thn_kelahiran'] ?? '',
                'tempat' => $data['tmp'] ?? '',
                'usia' => $data['usia'] ?? '',
                'description' => $data['description'] ?? ''
            ]
        ]
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode(array_merge(
        $credit,
        ['status' => false, 'message' => 'Data tidak ditemukan (HTTP ' . $http_code . ')']
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>