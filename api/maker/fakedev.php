<?php

/**
 * URL encode manual (sama persis dengan fungsi encode di C++)
 */
function encode($v) {
    $out = '';
    $len = strlen($v);
    for ($i = 0; $i < $len; $i++) {
        $c = $v[$i];
        $ascii = ord($c);
        
        if (ctype_alnum($c) || $c == '-' || $c == '_' || $c == '.' || $c == '~') {
            $out .= $c;
        } else {
            $out .= '%';
            $out .= "0123456789ABCDEF"[$ascii >> 4];
            $out .= "0123456789ABCDEF"[$ascii & 0xF];
        }
    }
    return $out;
}

/**
 * Menampilkan cara penggunaan (sama seperti C++)
 */
function usage() {
    echo "pakai: php fakedev.php -profile \"url\" -name \"nama\" -bio \"bio\"\n";
}

// Parse command line arguments (sama seperti C++)
$profile = '';
$name = '';
$bio = '';

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg == '-profile' && isset($argv[$i + 1])) {
        $profile = $argv[++$i];
    } elseif ($arg == '-name' && isset($argv[$i + 1])) {
        $name = $argv[++$i];
    } elseif ($arg == '-bio' && isset($argv[$i + 1])) {
        $bio = $argv[++$i];
    }
}

// Validasi input (sama seperti C++)
if (empty($profile) || empty($name) || empty($bio)) {
    usage();
    exit(1);
}

echo "mengambil gambar...\n";

try {
    // Build URL (sama seperti C++)
    $url = "https://api.azbry.com/api/maker/fakedev?img=" . encode($profile) . "&name=" . encode($name) . "&bio=" . encode($bio);
    
    // Inisialisasi CURL (mirip dengan http_client di C++)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    // Eksekusi request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Cek error CURL (mirip dengan exception handling di C++)
    if (curl_error($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    
    // Cek status code (sama seperti C++)
    if ($httpCode !== 200) {
        throw new Exception("gagal: " . $httpCode);
    }
    
    // Simpan file (mirip dengan file_stream di C++)
    $filename = "fakedev.jpg";
    if (file_put_contents($filename, $response) === false) {
        throw new Exception("gagal menyimpan file");
    }
    
    echo "tersimpan: " . $filename . "\n";
    
} catch (Exception $e) {
    echo "error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
