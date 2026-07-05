<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Loli - Foto cute anime urut dari koleksi (loop)
// Contoh: {} (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$images = [
    'https://i.pinimg.com/originals/04/82/f4/0482f447e372b130624d4e986f49a39e.jpg',
    'https://i.pinimg.com/originals/a0/80/a9/a080a9465decb49baedfa16ba977f6c0.jpg',
    'https://i.pinimg.com/originals/c4/84/42/c48442dcecc860ccd59490f87dc88f5a.jpg',
    'https://i.pinimg.com/originals/f6/8c/da/f68cda2164869e94365e2712f79ab5e8.png',
    'https://i.pinimg.com/originals/f5/6a/87/f56a87d1d56b3e44233eae545a5f8651.png',
    'https://i.pinimg.com/originals/15/5b/74/155b74c9891da68327607ee68550ef23.jpg',
    'https://i.pinimg.com/originals/60/ff/14/60ff1494f4ff3fb6cf4c6bf5a836a00d.png',
    'https://i.pinimg.com/originals/28/59/c1/2859c139410440c5e46086532ff12d15.jpg',
    'https://i.pinimg.com/originals/23/fd/ea/23fdea61f0038556e8affadcc265978b.jpg',
    'https://i.pinimg.com/originals/f7/9a/73/f79a73413874637557018d72138428ef.jpg',
    'https://i.pinimg.com/originals/3b/f1/7f/3bf17f3256f98d948e7d7c113554862a.jpg',
    'https://i.pinimg.com/originals/0a/54/de/0a54de496ed0a336739a5d063c1908a3.jpg',
    'https://i.pinimg.com/originals/95/0a/7f/950a7ff482ceca7a45930052e5def438.jpg',
    'https://i.pinimg.com/originals/45/df/77/45df77012b41e4758068159b2417996f.png',
    'https://i.pinimg.com/originals/10/b4/9f/10b49f9c4da15528a47362af5b16b788.jpg',
    'https://i.pinimg.com/originals/a3/e4/48/a3e448936634fa5608f52da2c6233a83.jpg',
    'https://i.pinimg.com/originals/55/5b/4f/555b4f3d965908066c6b5b63a5065a18.jpg',
    'https://i.pinimg.com/originals/a0/e2/9c/a0e29c0fabc3377b081ebc1e67ea4dc4.jpg',
    'https://i.pinimg.com/originals/98/df/69/98df6949fce8c87f15b03b306fc89fb0.jpg',
    'https://i.pinimg.com/originals/89/2c/33/892c334a7bd55683d3a8749182d43918.jpg'
];

$indexFile = sys_get_temp_dir() . '/loli_index.txt';

if (file_exists($indexFile)) {
    $index = (int)file_get_contents($indexFile);
    $index++;
    if ($index >= count($images)) {
        $index = 0;
    }
} else {
    $index = 0;
}

file_put_contents($indexFile, $index);

$selectedImage = $images[$index];

$ch = curl_init($selectedImage);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0']
]);

$imageData = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($imageData) {
    header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: no-cache');
    echo $imageData;
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'creator' => 'Nanzz',
        'status' => false,
        'message' => 'Gagal mengambil gambar'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>