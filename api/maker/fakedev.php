<?php
/**
 * fakedev.php
 * Konversi dari kode C++ (cpprest) ke PHP CLI.
 *
 * Pakai:
 *   php fakedev.php -profile "url" -name "nama" -bio "bio"
 */

function usage() {
    echo "pakai: php fakedev.php -profile \"url\" -name \"nama\" -bio \"bio\"" . PHP_EOL;
}

function main($argv) {
    $profile = '';
    $name    = '';
    $bio     = '';

    $argc = count($argv);
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '-profile' && $i + 1 < $argc) {
            $profile = $argv[++$i];
        } elseif ($arg === '-name' && $i + 1 < $argc) {
            $name = $argv[++$i];
        } elseif ($arg === '-bio' && $i + 1 < $argc) {
            $bio = $argv[++$i];
        }
    }

    if ($profile === '' || $name === '' || $bio === '') {
        usage();
        exit(1);
    }

    // rawurlencode() di PHP setara dengan fungsi encode() manual di kode C++
    $url = "https://api.azbry.com/api/maker/fakedev"
         . "?img="  . rawurlencode($profile)
         . "&name=" . rawurlencode($name)
         . "&bio="  . rawurlencode($bio);

    echo "mengambil gambar..." . PHP_EOL;

    // Gunakan cURL untuk request GET
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $body = curl_exec($ch);

    if ($body === false) {
        fwrite(STDERR, "error: " . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        exit(1);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        fwrite(STDERR, "gagal: " . $statusCode . PHP_EOL);
        exit(1);
    }

    $saved = file_put_contents('fakedev.jpg', $body);
    if ($saved === false) {
        fwrite(STDERR, "error: gagal menyimpan file fakedev.jpg" . PHP_EOL);
        exit(1);
    }

    echo "tersimpan: fakedev.jpg" . PHP_EOL;
    exit(0);
}

main($argv);
