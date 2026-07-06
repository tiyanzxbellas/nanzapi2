<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random PAP - Foto urut dari koleksi (loop)
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
    'https://i.pinimg.com/originals/6a/74/83/6a74838448f8b1238c69c8e3787f4e1b.jpg',
    'https://i.pinimg.com/originals/56/d1/93/56d1933c5344abe70dd9547e2266f929.jpg',
    'https://i.pinimg.com/originals/ac/47/7e/ac477e7923915d16545a37be76a3ef2f.jpg',
    'https://i.pinimg.com/originals/3b/3f/eb/3b3feb9a162c39ca4323860501ee7ed8.jpg',
    'https://i.pinimg.com/originals/47/b0/5a/47b05a20fe344fdc5b3344397247154e.jpg',
    'https://i.pinimg.com/originals/95/58/af/9558af3c328d53afcaff6cb549049641.png',
    'https://i.pinimg.com/originals/21/ce/55/21ce556835d64b71f29434b53dcb0de2.jpg',
    'https://i.pinimg.com/originals/62/bb/fc/62bbfc05a07d18a13e09acf43253395b.jpg',
    'https://i.pinimg.com/originals/6a/7b/9b/6a7b9bd7bdd17e4619c1412674d8ded9.jpg',
    'https://i.pinimg.com/originals/20/87/d9/2087d910a44b6a1a926f3e596b26337b.png',
    'https://i.pinimg.com/originals/69/46/fb/6946fb3eb3856697a2ba15b4a037d8a6.jpg',
    'https://i.pinimg.com/originals/17/62/db/1762db3130bb6a32fde43aa10dfcaa04.jpg',
    'https://i.pinimg.com/originals/2f/47/da/2f47da4eb4699619aaff790a7e8ea167.jpg',
    'https://i.pinimg.com/originals/e4/8c/a6/e48ca6653a781efc0807e6f797a31f09.jpg',
    'https://i.pinimg.com/originals/96/4f/5c/964f5c6cfa89285a9f458a1bbaa35824.jpg',
    'https://i.pinimg.com/originals/f9/d3/d8/f9d3d8518b8e918015c50be33ea9b6ed.jpg',
    'https://i.pinimg.com/originals/3c/b6/d3/3cb6d30cda4b7c020481de44e0011409.jpg',
    'https://i.pinimg.com/originals/e0/83/13/e08313eefc4233a33ae4830377e17b88.png',
    'https://i.pinimg.com/originals/96/4f/11/964f11bce888af5f185ead215506d3b9.jpg',
    'https://i.pinimg.com/originals/c5/d1/b4/c5d1b4ddbfa43188ca50ac710e6263bd.jpg',
    // Batch 2
    'https://i.pinimg.com/originals/e9/4d/6c/e94d6c6bcad6ee23c119b7582d06c297.jpg',
    'https://i.pinimg.com/originals/c1/96/47/c19647e4ca8e7c0346799df03a68e0ec.jpg',
    'https://i.pinimg.com/originals/f6/8d/08/f68d0809c9f02296e27a95cc3610f7fe.jpg',
    'https://i.pinimg.com/originals/f3/56/d7/f356d75a73d6a4cf627fca3e86435c8e.jpg',
    'https://i.pinimg.com/originals/7f/77/57/7f7757191024e787d9ee9c4d615b46e7.jpg',
    'https://i.pinimg.com/originals/12/b3/ab/12b3ab0ab2c2c9d3e0798a06e2c36222.jpg',
    'https://i.pinimg.com/originals/8c/0d/78/8c0d78d2363bc4828112a3950e4e6cde.jpg',
    'https://i.pinimg.com/originals/5b/0c/bc/5b0cbc9df4bc218ce364d103a53acb21.jpg',
    'https://i.pinimg.com/originals/b8/43/dd/b843dd246c216c1f07a18c501f81dcd9.jpg',
    'https://i.pinimg.com/originals/9e/9b/cd/9e9bcd09ece56ca5422ebd9c4672fb90.jpg',
    'https://i.pinimg.com/originals/2e/8c/32/2e8c32843f910df0d5aa2540c50d9a9c.jpg',
    'https://i.pinimg.com/originals/a2/27/cd/a227cdb9b51c3e99a98525964cad8362.jpg',
    'https://i.pinimg.com/originals/54/42/00/5442007aa752385ad27816c2c136cd5e.jpg',
    'https://i.pinimg.com/originals/f9/f8/75/f9f875e95dbb92b025cfe5bfd48e9f48.jpg',
    'https://i.pinimg.com/originals/30/f9/d6/30f9d6a7d086305a5bf1054d6e3a3e16.jpg',
    'https://i.pinimg.com/originals/dd/d7/21/ddd72108fd5a31eeec227a090c35bf2a.jpg',
    'https://i.pinimg.com/originals/9c/46/2d/9c462d5ba12b0e6b411982528964bcba.jpg'
];

// Simpan index di file
$indexFile = sys_get_temp_dir() . '/pap_index.txt';

if (file_exists($indexFile)) {
    $index = (int)file_get_contents($indexFile);
    $index++;
    if ($index >= count($images)) {
        $index = 0; // Balik ke awal
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