<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Multi Game Part 2 - 10 Game
// Contoh: {"game":"tebaklagu"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param game (tebaklagu|tebakgame|tebakjkt|tebakkarakterff|tebakkartun|tebaklirik|siapakahaku|tekateki|tebakheroml|tebakbendera) Game

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Tiyanz'];
set_time_limit(15);

$game = $_GET['game'] ?? 'tebaklagu';

$urls = [
    'tebaklagu' => 'https://raw.githubusercontent.com/qisyana/scrape/main/tebaklagu.json',
    'tebakgame' => 'https://raw.githubusercontent.com/qisyana/scrape/main/tebakgame.json',
    'tebakjkt' => 'https://raw.githubusercontent.com/siputzx/tebak-jkt/refs/heads/main/tebak.json',
    'tebakkarakterff' => 'https://raw.githubusercontent.com/siputzx/karakter-freefire/refs/heads/main/data.json',
    'tebaklirik' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tebaklirik.json',
    'siapakahaku' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/siapakahaku.json',
    'tekateki' => 'https://raw.githubusercontent.com/BochilTeam/database/master/games/tekateki.json',
    'tebakkartun' => null,
    'tebakheroml' => null,
    'tebakbendera' => null,
];

if (!isset($urls[$game])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Game tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

if ($game === 'tebakbendera') {
    $codes = json_decode(file_get_contents('https://flagcdn.com/en/codes.json'), true);
    $keys = array_keys($codes); $key = $keys[array_rand($keys)];
    $result = ['name' => $codes[$key], 'img' => "https://flagpedia.net/data/flags/ultra/{$key}.png"];
    echo json_encode(array_merge($credit, ['status' => true, 'game' => $game, 'result' => $result]), JSON_PRETTY_PRINT);
    exit;
}

if ($game === 'tebakkartun') {
    $result = ['name' => 'SpongeBob', 'img' => 'https://i.pinimg.com/736x/d2/b2/49/d2b2493f88da017b20b2f5ae1ad6be86.jpg'];
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