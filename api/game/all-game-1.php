<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Multi Game 1 - 11 Game
// Contoh: {"game":"caklontong"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param game (caklontong|family100|lengkapikalimat|susunkata|tebaktebakan|asahotak|tebakbendera|tebakgambar|tebakkalimat|tebakkata|tebakkimia) Game

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];
set_time_limit(15);

$game = $_GET['game'] ?? 'caklontong';

$urls = [
    'caklontong' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/caklontong.json',
    'family100' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/family100.json',
    'lengkapikalimat' => 'https://raw.githubusercontent.com/qisyana/scrape/main/lengkapikalimat.json',
    'susunkata' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/susunkata.json',
    'tebaktebakan' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebaktebakan.json',
    'asahotak' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/asahotak.json',
    'tebakgambar' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebakgambar.json',
    'tebakkalimat' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebakkalimat.json',
    'tebakkata' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebakkata.json',
    'tebakkimia' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebakkimia.json',
    'tebaksurah' => 'https://api.alquran.cloud/v1/ayah/' . rand(1, 6236) . '/ar.alafasy',
];

if (!isset($urls[$game])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Game tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

// Special: tebakbendera
if ($game === 'tebakbendera') {
    $codes = json_decode(file_get_contents('https://flagcdn.com/en/codes.json'), true);
    $keys = array_keys($codes); $key = $keys[array_rand($keys)];
    $result = ['name' => $codes[$key], 'img' => "https://flagpedia.net/data/flags/ultra/{$key}.png"];
    echo json_encode(array_merge($credit, ['status' => true, 'game' => $game, 'result' => $result]), JSON_PRETTY_PRINT);
    exit;
}

$ch = curl_init($urls[$game]);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
$response = curl_exec($ch); curl_close($ch);
$data = json_decode($response, true);
if (!$data) { echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal']), JSON_PRETTY_PRINT); exit; }

$result = isset($data['data']) ? $data['data'] : $data[array_rand($data)];
echo json_encode(array_merge($credit, ['status' => true, 'game' => $game, 'result' => $result]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>