<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - Daftar Online Card Generator (GD Library)
// Contoh: {"nama": "Rizky", "umur": "25"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param nama Text Input - Nama lengkap
// @param umur Text Input - Umur (default: 18)

header('Content-Type: image/png');
header("Access-Control-Allow-Origin: *");

$nama = $_GET['nama'] ?? 'Pengguna';
$umur_param = $_GET['umur'] ?? '18';

// Load background
$bg_url = 'https://api-nanas.my.id/daftar.png';
$bg = @imagecreatefrompng($bg_url);
if (!$bg) {
    $bg = imagecreatetruecolor(1300, 1000);
    $dark = imagecolorallocate($bg, 20, 20, 30);
    imagefill($bg, 0, 0, $dark);
}

// Warna hijau neon
$green = imagecolorallocate($bg, 57, 255, 20);

// Download font
$font_local = sys_get_temp_dir() . '/cormorant.ttf';
if (!file_exists($font_local)) {
    $ch = curl_init('https://nanzzcode.my.id/CormorantGaramond-SemiBold.ttf');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false]);
    $font_data = curl_exec($ch); curl_close($ch);
    if ($font_data) file_put_contents($font_local, $font_data);
}

// Data real-time (tanggal & jam execute)
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tanggal = date('d') . ' ' . $bulan[intval(date('m'))] . ' ' . date('Y');
$jam = date('H:i') . ' WIB';
$umur = $umur_param . ' tahun';

// Koordinat
// Nama: 887-1246, 555-628 → X:1065, Y:605
// Tanggal: 898-1249, 650-726 → X:1070, Y:705
// Jam: 890-1248, 753-828 → X:1065, Y:805
// Umur: 894-1031, 849-926 → X:960, Y:900

if (file_exists($font_local)) {
    imagettftext($bg, 28, 0, 1065, 605, $green, $font_local, $nama);
    imagettftext($bg, 24, 0, 1070, 705, $green, $font_local, $tanggal);
    imagettftext($bg, 24, 0, 1065, 805, $green, $font_local, $jam);
    imagettftext($bg, 22, 0, 960, 900, $green, $font_local, $umur);
} else {
    imagestring($bg, 5, 1065, 595, $nama, $green);
    imagestring($bg, 5, 1070, 695, $tanggal, $green);
    imagestring($bg, 5, 1065, 795, $jam, $green);
    imagestring($bg, 5, 960, 890, $umur, $green);
}

imagepng($bg);
imagedestroy($bg);
if (file_exists($font_local)) @unlink($font_local);
exit;
?>