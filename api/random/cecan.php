<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Cewe Cantik
// Contoh: {"country":"thailand"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param country (thailand|indonesia|malaysia|vietnam|japan|china|korea) Negara

header('Content-Type: image/jpeg');

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];
set_time_limit(15);

$country = $_GET['country'] ?? 'thailand';

$images = [
    'thailand' => [
        'https://i.pinimg.com/originals/f8/35/49/f83549c84b798f5ed761369437b5f804.jpg',
        'https://i.pinimg.com/originals/0f/91/fb/0f91fb2a6e45f4cbb686808dc26c8894.jpg',
        'https://i.pinimg.com/originals/32/a1/05/32a1055e8d460c55241cbacc0ad0f19b.jpg',
        'https://i.pinimg.com/originals/ed/3f/22/ed3f22f0cba1039f65aef4ec4a5bf3e3.png',
        'https://i.pinimg.com/originals/e1/31/bf/e131bf2d8de85605ea649ee66cdf6099.jpg',
        'https://i.pinimg.com/originals/12/a7/9e/12a79e8f6a2b62ede66cf5d0bb5aa837.jpg',
        'https://i.pinimg.com/originals/2b/28/93/2b28939a454d1fd0de7ff7f8d1b3fc08.jpg',
        'https://i.pinimg.com/originals/20/87/d9/2087d910a44b6a1a926f3e596b26337b.png',
        'https://i.pinimg.com/originals/e9/8f/00/e98f00b26c96795753339db66a05a479.png',
        'https://i.pinimg.com/originals/db/bb/13/dbbb139ba89abe388bb7049b43486c15.jpg',
        'https://i.pinimg.com/originals/aa/2a/ac/aa2aac3c41fdf7876ece6a8f4d68fd07.jpg',
        'https://i.pinimg.com/originals/c7/12/78/c712786e1b8d7eb69726e2b3e8b331e3.png',
        'https://i.pinimg.com/originals/37/40/2d/37402d766a58361bc46bd4d6a6a1cf01.jpg',
        'https://i.pinimg.com/originals/74/f1/2b/74f12ba7b0301379e1280910b75d555f.jpg',
        'https://i.pinimg.com/originals/66/05/c4/6605c46c15b71cc4794e55cde8125aa3.jpg',
        'https://i.pinimg.com/originals/b2/27/1e/b2271ebb8424b2576ec40a09a7644a23.jpg',
        'https://i.pinimg.com/originals/bc/60/63/bc60633a72cff9a0528b30badf975e0c.jpg',
        'https://i.pinimg.com/originals/58/8a/ba/588abae74803fa83f70d0497742dad5d.jpg',
        'https://i.pinimg.com/originals/40/a3/c0/40a3c082faf3a4528651367d075d0618.jpg',
        'https://i.pinimg.com/originals/8b/91/a1/8b91a1b9934c409f278a2d6e75dc9876.png'
    ],
    'indonesia' => [
        'https://i.pinimg.com/originals/46/74/fe/4674fe767ff7666289e720025246734f.jpg',
        'https://i.pinimg.com/originals/39/a2/94/39a29415fa150b8e3448723d542a9b58.jpg',
        'https://i.pinimg.com/originals/18/20/fe/1820feab2c38fa8fb5a3c506804f876a.jpg',
        'https://i.pinimg.com/originals/2a/77/53/2a7753370377b66e378667f469f0d672.jpg',
        'https://i.pinimg.com/originals/9b/2b/3c/9b2b3c026bb6321c47b78e047c2a2a4b.jpg',
        'https://i.pinimg.com/originals/1c/86/8e/1c868e79240306e871f5a4eac0b6bff3.jpg',
        'https://i.pinimg.com/originals/fb/87/89/fb8789c768a19fac25e8678c937656a9.jpg',
        'https://i.pinimg.com/originals/ad/87/82/ad8782b5867b73389c0ab231d3b1920e.jpg',
        'https://i.pinimg.com/originals/53/04/6f/53046fcc0f1aaced5bd6b9ce07e956cb.jpg',
        'https://i.pinimg.com/originals/f9/ea/b4/f9eab4c27f853cf42034e61b40b2022c.jpg',
        'https://i.pinimg.com/originals/b4/1e/bb/b41ebb1adce37258e14204930a740376.jpg',
        'https://i.pinimg.com/originals/49/fb/42/49fb4251df7cf69f66ab7fb359ae64a9.jpg',
        'https://i.pinimg.com/originals/24/e8/b9/24e8b932c859b4e78281f53082ee857c.jpg',
        'https://i.pinimg.com/originals/14/9b/71/149b71586abf8ec05afcd565fe5cab15.jpg',
        'https://i.pinimg.com/originals/47/b0/5a/47b05a20fe344fdc5b3344397247154e.jpg',
        'https://i.pinimg.com/originals/48/dc/c0/48dcc0b683216040be9ce62af7d18299.jpg',
        'https://i.pinimg.com/originals/3a/03/6c/3a036cd96ea8d8c49eac1f75e1cfb299.jpg',
        'https://i.pinimg.com/originals/11/80/1b/11801bacf646ce2d6397159c2e7891c5.jpg',
        'https://i.pinimg.com/originals/2e/9d/35/2e9d353bf90b20ff32e996036361a78c.jpg',
        'https://i.pinimg.com/originals/48/df/2b/48df2b201b5b3189a4bebfc2b5a330a5.jpg'
    ],
    'malaysia' => [
        'https://i.pinimg.com/originals/ac/87/e5/ac87e5af7343ac0d5bedffbdd7a28185.png',
        'https://i.pinimg.com/originals/d4/44/fe/d444fe6bacb480219d34f0ccfd7f4b47.jpg',
        'https://i.pinimg.com/originals/a5/06/fe/a506fe6aff7a355b9f50ef455ae9faf8.jpg',
        'https://i.pinimg.com/originals/47/4f/ab/474fab30c8f145d915ff64fffedcddae.jpg',
        'https://i.pinimg.com/originals/40/0e/a3/400ea35bbb1eda3a7b850ba389255e6e.jpg',
        'https://i.pinimg.com/originals/ca/11/41/ca114140af1454bffee7ec24275466d2.jpg',
        'https://i.pinimg.com/originals/1d/bd/d4/1dbdd43ec490f91dad6ad89cc835f6ef.jpg',
        'https://i.pinimg.com/originals/6b/34/22/6b3422d37ece2f9c2fbf2afc4ed5e417.jpg',
        'https://i.pinimg.com/originals/47/9e/28/479e28966566e0a15a035d806875c8ad.jpg',
        'https://i.pinimg.com/originals/f7/57/cf/f757cf0f0181d0385ffc33e0874ee790.heic',
        'https://i.pinimg.com/originals/ff/18/a1/ff18a147c3b4d9113838b42398227705.jpg',
        'https://i.pinimg.com/originals/cc/b2/e3/ccb2e36bb56606ee75224e397b71cf6d.jpg',
        'https://i.pinimg.com/originals/9a/df/82/9adf8299e4c3946c4adc8a290046c6a8.jpg',
        'https://i.pinimg.com/originals/c5/e9/98/c5e998cb8cf4d41ded3513d69c73967e.jpg',
        'https://i.pinimg.com/originals/00/8b/67/008b67885b9fa01b81a68ad0e3be315d.heic',
        'https://i.pinimg.com/originals/d6/60/87/d660870bdb9f15cb152960821b478f15.jpg',
        'https://i.pinimg.com/originals/c8/29/ed/c829edfb71b7207bb2ffdfd4f18d7626.jpg',
        'https://i.pinimg.com/originals/9d/77/a3/9d77a383e4eacf4c31a446d7dc920a75.jpg',
        'https://i.pinimg.com/originals/0c/18/88/0c1888afd19646523c36ca6509cd2bf1.jpg',
        'https://i.pinimg.com/originals/9e/13/ce/9e13ce366ae8a49679a5811f9e302822.jpg'
    ],
    'vietnam' => [
        'https://i.pinimg.com/originals/a4/b6/47/a4b64751bd605d0f2d91856603b95c06.jpg',
        'https://i.pinimg.com/originals/b2/03/6a/b2036a7bde6e81c07fc7b79c11fc6f1f.jpg',
        'https://i.pinimg.com/originals/82/d0/48/82d048c108faa43c12760c27582d183a.jpg',
        'https://i.pinimg.com/originals/06/bb/f9/06bbf9c72d193840cdac865297330e78.jpg',
        'https://i.pinimg.com/originals/60/50/b4/6050b43f10a2cdf0005563d0085f2069.jpg',
        'https://i.pinimg.com/originals/6b/ac/bc/6bacbcab1db03955dee6d302b698ddc4.jpg',
        'https://i.pinimg.com/originals/fb/3f/53/fb3f5378975c7265117a0199ebf40266.jpg',
        'https://i.pinimg.com/originals/9b/a3/93/9ba393f7022447135c6a105b9d8e26d6.jpg',
        'https://i.pinimg.com/originals/1b/a0/cc/1ba0ccb036fc01017eae166950df7611.png',
        'https://i.pinimg.com/originals/19/06/7a/19067ac0418b976437da2743828ca32a.png',
        'https://i.pinimg.com/originals/cc/1e/40/cc1e40f04d5e1372b2132d77fcbab9f6.jpg',
        'https://i.pinimg.com/originals/8d/8f/7e/8d8f7edbcdb1e95637d6ac96f799a4b3.jpg',
        'https://i.pinimg.com/originals/be/ac/25/beac25a566ef720ae786cfbbdea1b95f.jpg',
        'https://i.pinimg.com/originals/15/ee/8f/15ee8f0adb2d7af6ca225eb7af2a0eee.jpg',
        'https://i.pinimg.com/originals/ab/ef/82/abef82ea656f92ecb5a1b776b5fae248.jpg',
        'https://i.pinimg.com/originals/31/5f/c1/315fc1e3993e32d8a11992dab6774754.jpg',
        'https://i.pinimg.com/originals/3a/a1/48/3aa14807e52f135bb556111ca266078a.jpg',
        'https://i.pinimg.com/originals/07/4a/da/074ada5c5fe9728c2e10c4c803d33bda.jpg'
    ],
    'japan' => [
        'https://i.pinimg.com/originals/85/ac/71/85ac713abb983adc152344dcd041e565.jpg',
        'https://i.pinimg.com/originals/42/d8/c8/42d8c8b8a658ddd8779bc8f6100c2120.jpg',
        'https://i.pinimg.com/originals/ab/8c/0d/ab8c0d84f61d6a07365929cee26e1be4.jpg',
        'https://i.pinimg.com/originals/4e/cc/4b/4ecc4b5c3ced19af57cc47a228aaf531.png',
        'https://i.pinimg.com/originals/6d/f1/89/6df18912b2fb35734a83d571acb1e981.jpg',
        'https://i.pinimg.com/originals/36/81/27/36812783dfd8e3444128cfe9cc05982f.jpg',
        'https://i.pinimg.com/originals/d2/43/71/d24371fe8a5d7b9d7916c7013081126c.jpg',
        'https://i.pinimg.com/originals/dd/49/05/dd4905157e25c840f9919e70ece6cf5c.jpg',
        'https://i.pinimg.com/originals/5c/eb/10/5ceb106c9389db761cf3f8fd8e0ba4b0.jpg',
        'https://i.pinimg.com/originals/67/ef/d0/67efd0ccb74536a7912628b0fe93d607.jpg',
        'https://i.pinimg.com/originals/1b/0d/37/1b0d37a0b661dd0ef971c0b5c1fb28c6.jpg',
        'https://i.pinimg.com/originals/7e/75/b0/7e75b039e7c3d48d569ee3dae8761dda.jpg',
        'https://i.pinimg.com/originals/62/6a/04/626a04ba168a2cbe8b31ebe84061a693.jpg',
        'https://i.pinimg.com/originals/ab/b9/a2/abb9a2f59fe20cd23dea02e79c3ce66a.png',
        'https://i.pinimg.com/originals/d3/3c/d0/d33cd06b7d7b9624cb2d75e3b5c2b13d.jpg',
        'https://i.pinimg.com/originals/0b/07/f8/0b07f8efd247a279f18d24f72bbfa997.jpg',
        'https://i.pinimg.com/originals/19/ed/3b/19ed3b18846f3b40a3a47e2a7bbd1f3b.jpg',
        'https://i.pinimg.com/originals/8d/38/49/8d384976598739555acb9a1a70fa75e9.jpg',
        'https://i.pinimg.com/originals/0b/26/65/0b26654adccc3eaadee985ac591bd107.jpg',
        'https://i.pinimg.com/originals/35/f5/2f/35f52ff3025b61422436142eac7ea6e8.jpg'
    ],
    'china' => [
        'https://i.pinimg.com/originals/59/f7/61/59f76117bb98955e1ec56f6d77ec7b69.jpg',
        'https://i.pinimg.com/originals/c2/cd/ff/c2cdff162acc7eded8ec452f635f3eb8.jpg',
        'https://i.pinimg.com/originals/5c/e0/3e/5ce03effab92288d346718642af44b7b.jpg',
        'https://i.pinimg.com/originals/a5/05/c0/a505c0583fb1dcf532ed7e6fbe6dd8f6.webp',
        'https://i.pinimg.com/originals/fe/15/0c/fe150c7a7694d59407fd0ce07153859e.jpg',
        'https://i.pinimg.com/originals/12/a6/2d/12a62d3eb211247647bf800bbbe25818.jpg',
        'https://i.pinimg.com/originals/e4/dd/bc/e4ddbc3088a02673c3c5da3c98e3aa8f.jpg',
        'https://i.pinimg.com/originals/39/ac/23/39ac23767737650c3679e29d5d962da1.jpg',
        'https://i.pinimg.com/originals/91/02/43/91024335ac750a554ebc23bb3087a9ea.jpg',
        'https://i.pinimg.com/originals/de/55/d7/de55d7771f0d9fd78fd069c2b50b701c.jpg',
        'https://i.pinimg.com/originals/dc/4f/c3/dc4fc3bd30ba35454544978641551838.jpg',
        'https://i.pinimg.com/originals/4d/17/29/4d17292cdb2208213eb5b7cb9afebd8c.jpg',
        'https://i.pinimg.com/originals/82/d0/48/82d048c108faa43c12760c27582d183a.jpg',
        'https://i.pinimg.com/originals/06/77/0d/06770de11868c5a4fbebd81a63ddcc93.jpg',
        'https://i.pinimg.com/originals/4f/04/53/4f04532778eba6d9227780ec526dee94.jpg',
        'https://i.pinimg.com/originals/4e/cc/4b/4ecc4b5c3ced19af57cc47a228aaf531.png',
        'https://i.pinimg.com/originals/a4/83/87/a483872ed886088f8c72527d46281326.jpg',
        'https://i.pinimg.com/originals/ca/cb/45/cacb45354e8ca120f8a91b438557b044.jpg',
        'https://i.pinimg.com/originals/de/7d/d4/de7dd4eeb0aeddc512567703dff1afe0.jpg'
    ],
    'korea' => [
        'https://i.pinimg.com/originals/73/85/d6/7385d60c7a168fd851ee08ee6eb3cb76.png',
        'https://i.pinimg.com/originals/4e/cc/4b/4ecc4b5c3ced19af57cc47a228aaf531.png',
        'https://i.pinimg.com/originals/9e/e5/d6/9ee5d6142502c8c6a24f54acafccdf20.jpg',
        'https://i.pinimg.com/originals/d3/8c/68/d38c68bd5dadaab37c72b337fe02aa41.jpg',
        'https://i.pinimg.com/originals/e2/bf/20/e2bf20f7517273b17ec46162fa7a1bf0.jpg',
        'https://i.pinimg.com/originals/85/15/da/8515da71991d24a0668c65186850b143.jpg',
        'https://i.pinimg.com/originals/84/0d/5c/840d5c9befcbe1f3bec563c877042037.jpg',
        'https://i.pinimg.com/originals/4f/df/c3/4fdfc3a4edc88caa4cd3b5e07c747e6d.jpg',
        'https://i.pinimg.com/originals/4b/f1/f2/4bf1f21c8ccc7e32626b6c63f329c09a.png',
        'https://i.pinimg.com/originals/1c/ea/9e/1cea9e61bbf8841d24b7169fd1fabb5f.jpg',
        'https://i.pinimg.com/originals/17/2f/6a/172f6a34099caccae2a48e31872d5e15.png',
        'https://i.pinimg.com/originals/a7/55/83/a75583cf9209f7b6ffb127d9a8f1587f.png',
        'https://i.pinimg.com/originals/ae/2b/50/ae2b50495662a78918c80773323e6718.png',
        'https://i.pinimg.com/originals/75/23/71/75237184506bcdd6cd23f7f0d4eeced1.jpg',
        'https://i.pinimg.com/originals/9e/49/03/9e49033105cc8d4118e28857bdbb9dff.jpg',
        'https://i.pinimg.com/originals/b9/42/d6/b942d67a48bfd155eca82a5029ae05b2.png',
        'https://i.pinimg.com/originals/b0/98/42/b0984227891390ab83a048495b55a1ac.jpg',
        'https://i.pinimg.com/originals/dd/f9/50/ddf950525ed622d4b4137a11435c7eaf.png',
        'https://i.pinimg.com/originals/8f/a0/1c/8fa01cd1433c3e34fbc2f47fe9a5d6a7.jpg',
        'https://i.pinimg.com/originals/47/d4/8a/47d48aba045c62efa5e781ed9230dbbe.jpg'
    ]
];

if (!isset($images[$country])) {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Negara tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

$randomImage = $images[$country][array_rand($images[$country])];

$ch = curl_init($randomImage);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false]);
$img = curl_exec($ch);
curl_close($ch);

if ($img) {
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($img));
    echo $img;
} else {
    header('Content-Type: application/json');
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Gagal fetch gambar']), JSON_PRETTY_PRINT);
}
?>