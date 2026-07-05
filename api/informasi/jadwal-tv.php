<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Jadwal TV Indonesia
// Contoh: {"channel":"sctv"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param channel Channel TV (kosongkan untuk semua channel)

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];
set_time_limit(30);

$channel = $_GET['channel'] ?? '';

$baseUrl = 'https://www.jadwaltv.net';
$url = $channel ? "$baseUrl/channel/" . strtolower($channel) : "$baseUrl/channel/acara-tv-nasional-saat-ini";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36']
]);

$html = curl_exec($ch);
curl_close($ch);

if (!$html) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal fetch data']), JSON_PRETTY_PRINT);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$jadwal = [];

if (!$channel) {
    // Semua channel
    $rows = $xpath->query("//table[contains(@class,'table-bordered')]/tbody/tr");
    $currentChannel = '';
    
    foreach ($rows as $row) {
        $tds = $row->getElementsByTagName('td');
        
        if ($tds->length === 1 && $tds[0]->getAttribute('colspan') === '2') {
            $a = $tds[0]->getElementsByTagName('a');
            if ($a->length > 0) $currentChannel = trim($a[0]->textContent);
        } elseif ($tds->length >= 2 && $currentChannel) {
            $jam = trim($tds[0]->textContent);
            $acara = trim($tds[1]->textContent);
            if ($jam && $acara) {
                $jadwal[$currentChannel][] = ['jam' => $jam, 'acara' => $acara];
            }
        }
    }
    
    $result = [];
    foreach ($jadwal as $ch => $list) {
        $result[] = ['channel' => $ch, 'jadwal' => $list];
    }
} else {
    // Channel spesifik
    $rows = $xpath->query("//table[contains(@class,'table-bordered')]/tbody/tr");
    foreach ($rows as $row) {
        $tds = $row->getElementsByTagName('td');
        if ($tds->length >= 2) {
            $jam = trim($tds[0]->textContent);
            $acara = trim($tds[1]->textContent);
            if ($jam && $acara && $jam !== 'Jam' && $acara !== 'Acara') {
                $result[] = ['jam' => $jam, 'acara' => $acara];
            }
        }
    }
}

echo json_encode(array_merge($credit, [
    'status' => !empty($result),
    'channel' => $channel ?: 'semua',
    'result' => $result
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>