<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Meme Presiden - Koleksi meme presiden urut (loop)
// Contoh: {} (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

set_time_limit(30);

$images = [
    'https://i.pinimg.com/originals/c7/b1/18/c7b118f3b04412643f105ec0023ead58.jpg',
    'https://i.pinimg.com/originals/e9/b8/e7/e9b8e7a5952c95ba81e575a44d8eebeb.jpg',
    'https://i.pinimg.com/originals/5e/c0/db/5ec0db037418503e34ae55178fbb5cd2.png',
    'https://i.pinimg.com/originals/d6/5a/0b/d65a0ba6b4a44d75e81d2528125acd04.png',
    'https://i.pinimg.com/originals/1e/58/fb/1e58fb65e26c14aa0fd959bec5011b95.jpg',
    'https://i.pinimg.com/originals/ae/10/76/ae10764e5bdbacbfc8ec6ccbd517d77f.jpg',
    'https://i.pinimg.com/originals/0e/fa/17/0efa17bb0840d2480daebc1a3adcfbbf.jpg',
    'https://i.pinimg.com/originals/39/ab/ca/39abca2bba417794d9a11f4d99db1483.jpg',
    'https://i.pinimg.com/originals/40/0e/ca/400eca825a5dda192babd041fe369cbc.jpg',
    'https://i.pinimg.com/originals/6e/0f/05/6e0f057d6d82cb6a1f1054c2b3504f92.jpg',
    'https://i.pinimg.com/originals/69/7d/1d/697d1de071f9681911966ea0bd3b413b.jpg',
    'https://i.pinimg.com/originals/29/ac/29/29ac2980f333fa2008934e17fde7e81c.jpg',
    'https://i.pinimg.com/originals/fd/04/a1/fd04a1ba3af8c11d33f92f70b27334c9.jpg',
    'https://i.pinimg.com/originals/b4/44/5c/b4445cc851639d50a49f64c82c897a4d.jpg',
    'https://i.pinimg.com/originals/ee/42/72/ee4272fd4bb45955c11d550025804ab5.jpg',
    'https://i.pinimg.com/originals/9c/4a/da/9c4adaf056df8a3bb8eeb90b9747f29b.jpg',
    'https://i.pinimg.com/originals/6e/f2/02/6ef20226900cf37a9d425bed0d15c10e.png',
    'https://i.pinimg.com/originals/0f/a5/c3/0fa5c362da1f857b8703129c3410ddab.jpg',
    'https://i.pinimg.com/originals/0a/d0/ef/0ad0ef861fd8789f27879699d3774c6e.jpg'
];

$indexFile = sys_get_temp_dir() . '/presiden_index.txt';

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