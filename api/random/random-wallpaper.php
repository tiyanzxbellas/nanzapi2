<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Wallpaper
// Contoh: (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

$images = [
    "https://i.pinimg.com/564x/3b/51/0b/3b510b9dc9ce95e068ebfb66cee8fcfb.jpg",
    "https://i.pinimg.com/originals/8b/f4/fa/8bf4fa5c4d4c00e52b9386da6d5e6723.jpg",
    "https://i.pinimg.com/originals/ce/00/24/ce002453879ef6c5eda3db249946d372.jpg",
    "https://i.pinimg.com/originals/47/76/b0/4776b0068a7de7e691a2f7b479818a9b.jpg",
    "https://i.pinimg.com/736x/12/ee/98/12ee982b710e5a73b26fc4952927c20c.jpg",
    "https://i.pinimg.com/736x/6c/48/b2/6c48b269655557eb8a1a86e3442c0818.jpg",
    "https://i.pinimg.com/originals/64/76/72/647672a241cca36e2c947e0240c45f11.jpg",
    "https://i.pinimg.com/originals/69/ba/8b/69ba8bcc41adf78dd9e242cc6d22ae26.jpg",
    "https://i.pinimg.com/736x/d7/ee/27/d7ee277c313e37955caa401ffd4aded1.jpg",
    "https://i.pinimg.com/474x/b6/21/ea/b621eabb5e1a0884de3484525c346e80.jpg",
    "https://i.pinimg.com/originals/f1/c0/f7/f1c0f71a593028f5721d8baa0c2a1f73.jpg",
    "https://i.pinimg.com/originals/3b/70/21/3b7021103f12cc4557e195d8adc89ca2.jpg",
    "https://i.pinimg.com/originals/62/0c/ba/620cbaf26cb6c211b4aaed49cc419aea.gif",
    "https://i.pinimg.com/564x/6c/5f/33/6c5f336ced201d98e5d6fe157178ebff.jpg",
    "https://i.pinimg.com/originals/bd/6c/3b/bd6c3b4e7166172bc1f27c59bd9c7e76.jpg",
    "https://i.pinimg.com/originals/63/57/66/635766e0012b1043b417569045677466.png",
    "https://i.pinimg.com/736x/6c/d1/51/6cd151fb83df27d4e06f81d8633d3d94.jpg",
    "https://i.pinimg.com/564x/5b/d3/f8/5bd3f8d6d524aa7e2bcd33de80ba835a.jpg",
    "https://i.pinimg.com/originals/12/02/30/120230f7b1a9fb8d619d6e048c00b72f.jpg",
    "https://files.catbox.moe/4y9e8i.jpg",
    "https://files.catbox.moe/66foej.jpg",
    "https://files.catbox.moe/kw71zy.jpg",
    "https://files.catbox.moe/lj2h9o.jpg",
    "https://files.catbox.moe/54v206.jpg",
    "https://files.catbox.moe/s0vx4r.jpg",
    "https://files.catbox.moe/kx1yoy.jpg",
    "https://files.catbox.moe/odr2w8.jpg",
    "https://files.catbox.moe/gn7sg7.jpg",
    "https://files.catbox.moe/gu8nbz.jpg"
];

shuffle($images);
$randomImage = $images[array_rand($images)];

$ch = curl_init($randomImage);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
$img = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code === 200 && !empty($img)) {
    header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
    header('X-Creator: Tiyanz');
    echo $img;
} else {
    header('Location: ' . $randomImage, true, 302);
}
?>