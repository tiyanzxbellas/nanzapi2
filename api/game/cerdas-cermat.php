<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Game Cerdas Cermat
// Contoh: {"mapel":"matematika","jumlahsoal":"5"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param mapel (bindo|tik|pkn|bing|penjas|pai|matematika|jawa|ips|ipa) Mata Pelajaran
// @param jumlahsoal Jumlah Soal (5-10)

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];
set_time_limit(15);

$mapel = $_GET['mapel'] ?? 'matematika';
$jumlahsoal = (int)($_GET['jumlahsoal'] ?? 5);
$jumlahsoal = max(5, min(10, $jumlahsoal));

$subjects = ['bindo','tik','pkn','bing','penjas','pai','matematika','jawa','ips','ipa'];
if (!in_array($mapel, $subjects)) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Mapel tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

$url = "https://gist.githubusercontent.com/siputzx/298d2d3bd5901494537b9848e35dab9f/raw/25f5dcfef0d97141c555c2bbb94fe1f3d1f76cb3/{$mapel}.json";
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
$response = curl_exec($ch);
curl_close($ch);

$all = json_decode($response, true);
if (!$all) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal fetch data']), JSON_PRETTY_PRINT);
    exit;
}

shuffle($all);
$soal = array_slice($all, 0, $jumlahsoal);

echo json_encode(array_merge($credit, ['status' => true, 'mapel' => $mapel, 'jumlah_soal' => $jumlahsoal, 'soal' => $soal]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>