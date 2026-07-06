<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Waifu - Foto waifu urut dari koleksi (loop)
// Contoh: {} (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$images = [
    // Batch 1
    'https://i.pinimg.com/originals/66/53/af/6653af2d6a8e3e5ee4a297d96fd1a161.jpg',
    'https://i.pinimg.com/originals/b8/7d/5e/b87d5e0e68042a321c4cba9cff487ff8.jpg',
    'https://i.pinimg.com/originals/67/92/62/6792629be32484fa1988d542f4eaec5a.jpg',
    'https://i.pinimg.com/originals/99/b1/08/99b108d833ce10ff05c708546cb68785.jpg',
    'https://i.pinimg.com/originals/fe/12/a3/fe12a36a4f8dd44542b357204a7f2a10.jpg',
    'https://i.pinimg.com/originals/1a/9a/e5/1a9ae5ca64c927854cdad8901c13b8cb.jpg',
    'https://i.pinimg.com/originals/22/ca/03/22ca03ec82583f806a7d645d77789b16.jpg',
    'https://i.pinimg.com/originals/69/23/30/692330aa964f002f2205a5a2f4443d3a.jpg',
    'https://i.pinimg.com/originals/61/55/2e/61552ebc3da83d612cb9a0293fb306ec.jpg',
    'https://i.pinimg.com/originals/cf/e4/87/cfe487268d2a38feec8d53cc3c307712.jpg',
    'https://i.pinimg.com/originals/ae/04/a5/ae04a599bdcdd7ebffbe0506dacb6fb5.jpg',
    'https://i.pinimg.com/originals/b7/8a/7c/b78a7c3849183f51cf81e8947cbf6ddf.jpg',
    'https://i.pinimg.com/originals/34/81/85/34818578c9c023d50446c4d5059059f2.jpg',
    'https://i.pinimg.com/originals/03/b7/ec/03b7ecdc1b022b15750c38216c3f0fc4.png',
    'https://i.pinimg.com/originals/40/f8/e3/40f8e3d0fc7abf67f57d42d9476ed491.jpg',
    'https://i.pinimg.com/originals/b3/04/92/b30492ac3ad284b29f764643ac2a4327.webp',
    'https://i.pinimg.com/originals/d2/64/6c/d2646c048c88effe9389f52ec29ac49f.jpg',
    'https://i.pinimg.com/originals/a2/ef/de/a2efde85220041ae1aaa465d1755cfa2.jpg',
    // Batch 2
    'https://i.pinimg.com/originals/46/e0/b1/46e0b197db2f9737f627f9693d5af726.jpg',
    'https://i.pinimg.com/originals/49/92/ff/4992ffe39918222e98aa80e9994f4102.jpg',
    'https://i.pinimg.com/originals/d4/ce/cf/d4cecfd4bc22dc638aad82e31e137459.jpg',
    'https://i.pinimg.com/originals/42/74/92/427492274b9fc8299fb621331d91a49a.jpg',
    'https://i.pinimg.com/originals/6d/4b/42/6d4b4243e230e21cbc8b03531eddd0bc.jpg',
    'https://i.pinimg.com/originals/6b/92/9d/6b929d39428f3a9e9bd21d0a6bde173d.jpg',
    'https://i.pinimg.com/originals/3f/d4/5f/3fd45f4e611bac5a78fe53504ec2f1d3.png',
    'https://i.pinimg.com/originals/63/70/3c/63703cc495d40b2ca3327739aaf1dfeb.jpg',
    'https://i.pinimg.com/originals/95/a4/41/95a441239a149c223586dc81f2ce801a.jpg',
    'https://i.pinimg.com/originals/33/79/62/337962079324a0e3859c53ebabf68324.jpg',
    'https://i.pinimg.com/originals/dc/e9/54/dce954a8310e08925d9c451b41194b31.jpg'
];

// Hapus duplikat
$images = array_values(array_unique($images));

$indexFile = sys_get_temp_dir() . '/waifu_index.txt';

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
        'creator' => 'Tiyanz',
        'status' => false,
        'message' => 'Gagal mengambil gambar'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>