<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: MLBB API - Item Build, Items, Emblems
// Contoh: {"action":"build","hero":"harley"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param action (build|items|emblems|ability) Mode
// @param hero Nama Hero (untuk build)

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];
set_time_limit(30);

$action = $_GET['action'] ?? 'items';
$hero = $_GET['hero'] ?? '';

define('BASE_URL', 'https://mlbb.io/api');

function mlbbRequest($endpoint) {
    $ch = curl_init(BASE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Origin: https://mlbb.io',
            'User-Agent: Mozilla/5.0'
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

try {
    switch ($action) {
        case 'build':
            if (!$hero) throw new Exception('Parameter hero diperlukan');
            $result = mlbbRequest('/item/item-build/hero/' . ucfirst($hero));
            break;
        case 'items':
            $result = mlbbRequest('/item/all-items');
            break;
        case 'emblems':
            $result = mlbbRequest('/emblem/main-emblems');
            break;
        case 'ability':
            $result = mlbbRequest('/emblem/ability-emblems');
            break;
        default:
            throw new Exception('Action tidak valid (build|items|emblems|ability)');
    }
    
    echo json_encode(array_merge($credit, [
        'status' => true,
        'action' => $action,
        'result' => $result
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => $e->getMessage()]), JSON_PRETTY_PRINT);
}
?>