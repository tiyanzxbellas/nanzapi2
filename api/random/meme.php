<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Meme - Meme Indonesia urut dari koleksi (loop)
// Contoh: {} (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Nanzz'
];

set_time_limit(30);

$images = [
    // Batch 1 - Random Meme Indonesia
    'https://i.pinimg.com/originals/8b/3a/f4/8b3af408098c811395efe70c30228533.jpg',
    'https://i.pinimg.com/originals/e0/d1/71/e0d171bb1c3d5dbb36f73eedf3df59fd.jpg',
    'https://i.pinimg.com/originals/9a/1e/db/9a1edb3a20db9a56dd8c7adc4a32ba6a.jpg',
    'https://i.pinimg.com/originals/0b/81/e3/0b81e32f12bb312e8f9655c9406644b2.jpg',
    'https://i.pinimg.com/originals/c8/f3/53/c8f353254804f2bb021fd65ad94e8331.jpg',
    'https://i.pinimg.com/originals/c5/fa/25/c5fa25a74ace1a1b5e351626a4bf6936.jpg',
    'https://i.pinimg.com/originals/b7/dc/31/b7dc3144b2e6a37ce8e546133c5a9d89.jpg',
    'https://i.pinimg.com/originals/93/f8/1b/93f81b10555fb22642980c218c3c8f73.jpg',
    'https://i.pinimg.com/originals/2d/0e/3f/2d0e3ff1db84d57ffaba27617206278a.jpg',
    'https://i.pinimg.com/originals/ea/6b/59/ea6b594392cbefae538cf75419ceacda.jpg',
    'https://i.pinimg.com/originals/76/60/52/766052ef4c097b1e53f9fb53b07cc2ad.jpg',
    'https://i.pinimg.com/originals/58/c3/5a/58c35a1577fc26c52c5c0eea6b3fdecb.jpg',
    'https://i.pinimg.com/originals/c1/45/86/c14586835445446d8781c8b3cb2383b8.jpg',
    'https://i.pinimg.com/originals/fd/dc/00/fddc00c4bd8928b0f06e3e54959858d1.jpg',
    'https://i.pinimg.com/originals/b7/31/26/b7312644e40d0355303f0889cf6fb6d3.jpg',
    'https://i.pinimg.com/originals/fc/f3/d8/fcf3d8cefd6a95925378347931a4c270.jpg',
    'https://i.pinimg.com/originals/6f/a3/d5/6fa3d54bd62033a0f8e092cbdaf21b08.jpg',
    'https://i.pinimg.com/originals/ef/b2/18/efb21890906b66a9f94c096a95dfdb79.jpg',
    'https://i.pinimg.com/originals/e7/f2/bc/e7f2bc0bc080c4f7651aaccbd3575463.jpg',
    // Batch 2 - Meme Indonesia
    'https://i.pinimg.com/originals/55/1e/70/551e70f008517fbf9366ac2dea656293.jpg',
    'https://i.pinimg.com/originals/84/f3/9a/84f39acd592286a6cff036579fb3f887.jpg',
    'https://i.pinimg.com/originals/3f/48/c5/3f48c5a382c71f19ac36d1685afc9109.jpg',
    'https://i.pinimg.com/originals/0c/e0/bc/0ce0bc7469850da9188a97597ace3010.jpg',
    'https://i.pinimg.com/originals/cb/41/4e/cb414ec0d28fbe6249dfe0c741774565.jpg',
    'https://i.pinimg.com/originals/7b/1b/78/7b1b7855b04c4e08bf30dd9cbbc70bb4.jpg',
    'https://i.pinimg.com/originals/f8/95/b2/f895b2e4eab0cf4ef69f6f582954d925.png',
    'https://i.pinimg.com/originals/7a/a3/90/7aa3908b86d07b04478d5e32e8bcf52e.jpg',
    'https://i.pinimg.com/originals/7e/62/8a/7e628ae0b2118cc54952d4778dfc4426.jpg',
    'https://i.pinimg.com/originals/95/75/fb/9575fb55e9b61270ef3eed9737d3ea64.jpg',
    'https://i.pinimg.com/originals/1a/82/8a/1a828a94320042bb3b6b5e64ccebf6fb.jpg',
    'https://i.pinimg.com/originals/f5/30/11/f530113e81028ee76c929306abade972.jpg'
];

// Hapus duplikat
$images = array_values(array_unique($images));

// Simpan index di file
$indexFile = sys_get_temp_dir() . '/meme_index.txt';

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