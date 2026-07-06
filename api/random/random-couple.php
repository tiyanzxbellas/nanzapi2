<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Couple PAP (Cowo Kiri + Cewe Kanan)
// Contoh: (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

set_time_limit(30);

$data = [
    ["cewe" => "https://files.catbox.moe/covxzy.jpg", "cowo" => "https://files.catbox.moe/onqo1a.jpg"],
    ["cewe" => "https://files.catbox.moe/xq8of0.jpg", "cowo" => "https://files.catbox.moe/50myst.jpg"],
    ["cewe" => "https://files.catbox.moe/m57z6w.jpg", "cowo" => "https://files.catbox.moe/bbfrg8.jpg"],
    ["cewe" => "https://files.catbox.moe/upfa24.jpg", "cowo" => "https://files.catbox.moe/fg18ob.jpg"],
    ["cewe" => "https://files.catbox.moe/570f4m.jpg", "cowo" => "https://files.catbox.moe/cnfii7.jpg"],
    ["cewe" => "https://files.catbox.moe/9t1z8u.jpg", "cowo" => "https://files.catbox.moe/vnpxrx.jpg"],
    ["cewe" => "https://files.catbox.moe/akaekm.jpg", "cowo" => "https://files.catbox.moe/yex8b6.jpg"],
    ["cewe" => "https://files.catbox.moe/g3i5es.jpg", "cowo" => "https://files.catbox.moe/yez8p6.jpg"],
    ["cewe" => "https://files.catbox.moe/6xv209.jpg", "cowo" => "https://files.catbox.moe/j6eu1g.jpg"],
    ["cewe" => "https://files.catbox.moe/j6bq37.jpg", "cowo" => "https://files.catbox.moe/uwu3bx.jpg"],
    ["cewe" => "https://files.catbox.moe/3ha3s5.jpg", "cowo" => "https://files.catbox.moe/aeud5a.jpg"],
    ["cewe" => "https://files.catbox.moe/rfmo10.jpg", "cowo" => "https://files.catbox.moe/lhvtx9.jpg"],
    ["cewe" => "https://files.catbox.moe/em5kai.jpg", "cowo" => "https://files.catbox.moe/oxu3zy.jpg"],
    ["cewe" => "https://files.catbox.moe/401z3h.jpg", "cowo" => "https://files.catbox.moe/pls5yl.jpg"],
    ["cewe" => "https://kyzzuploader.my.id/upload/033c.jpg", "cowo" => "https://kyzzuploader.my.id/upload/hse6.jpg"],
    ["cewe" => "https://kyzzuploader.my.id/upload/miq2.jpg", "cowo" => "https://kyzzuploader.my.id/upload/2ydy.jpg"],
    ["cewe" => "https://kyzzuploader.my.id/upload/6z6p.jpg", "cowo" => "https://kyzzuploader.my.id/upload/5uhj.jpg"],
    ["cewe" => "https://files.catbox.moe/xjfwav.jpg", "cowo" => "https://files.catbox.moe/4tyff4.jpg"],
    ["cewe" => "https://files.catbox.moe/3f6njf.jpg", "cowo" => "https://files.catbox.moe/yuzfv3.jpg"],
    ["cewe" => "https://files.catbox.moe/pfqb2a.jpg", "cowo" => "https://files.catbox.moe/8uvt9d.jpg"],
    ["cewe" => "https://files.catbox.moe/0x1tcl.jpg", "cowo" => "https://files.catbox.moe/tuoje3.jpg"],
    ["cewe" => "https://files.catbox.moe/nzxoi8.jpg", "cowo" => "https://files.catbox.moe/7yl0pt.jpg"],
    ["cewe" => "https://files.catbox.moe/fhlwyh.jpg", "cowo" => "https://files.catbox.moe/2ipyvw.jpg"],
    ["cewe" => "https://files.catbox.moe/zwyft2.jpg", "cowo" => "https://files.catbox.moe/50i1ab.jpg"],
    ["cewe" => "https://files.catbox.moe/lh35pq.jpg", "cowo" => "https://files.catbox.moe/8945km.jpg"],
    ["cewe" => "https://files.catbox.moe/3u3zmw.jpg", "cowo" => "https://files.catbox.moe/zfwj3d.jpg"],
    ["cewe" => "https://files.catbox.moe/nubb31.jpg", "cowo" => "https://files.catbox.moe/dcc2r6.jpg"],
    ["cewe" => "https://files.catbox.moe/zh3x7j.jpg", "cowo" => "https://files.catbox.moe/rerrfc.jpg"],
    ["cewe" => "https://files.catbox.moe/33w76q.jpg", "cowo" => "https://files.catbox.moe/77ar27.jpg"],
    ["cewe" => "https://files.catbox.moe/2o1t4f.jpg", "cowo" => "https://files.catbox.moe/jsvipv.jpg"],
    ["cewe" => "https://files.catbox.moe/wu2urz.jpg", "cowo" => "https://files.catbox.moe/7rcyma.jpg"]
];

shuffle($data);
$pair = $data[array_rand($data)];

// Coba download gambar
function getImg($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
    $img = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'data' => $img];
}

$cewe = getImg($pair['cewe']);
$cowo = getImg($pair['cowo']);

// Kalau gagal download, redirect ke JSON
if ($cewe['code'] !== 200 || $cowo['code'] !== 200) {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Tiyanz',
        'status' => true,
        'result' => $pair
    ]);
    exit;
}

// Gabung gambar
$ceweImg = imagecreatefromstring($cewe['data']);
$cowoImg = imagecreatefromstring($cowo['data']);

$cw = imagesx($ceweImg);
$ch = imagesy($ceweImg);
$mw = imagesx($cowoImg);
$mh = imagesy($cowoImg);

$maxH = max($ch, $mh);
$totalW = $cw + $mw;

$canvas = imagecreatetruecolor($totalW, $maxH);
imagecopyresampled($canvas, $cowoImg, 0, 0, 0, 0, $mw, $maxH, $mw, $mh);
imagecopyresampled($canvas, $ceweImg, $mw, 0, 0, 0, $cw, $maxH, $cw, $ch);

header('Content-Type: image/png');
header('X-Creator: Tiyanz');
imagepng($canvas);
imagedestroy($canvas);
imagedestroy($ceweImg);
imagedestroy($cowoImg);
?>