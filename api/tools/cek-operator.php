<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: RebornLookUp API - Indonesian Phone Number Lookup (IndoPhoneLookup)
// Contoh: {"number": "6283133096767"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param number Text Input - Nomor telepon Indonesia (628xx atau 08xx)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Tiyanz'];

// ========== KODE UTAMA ==========
$number = isset($_GET['number']) ? trim($_GET['number']) : '';

// Validasi input
if (empty($number)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => 'Phone number is required. Example: ?number=6283133096767'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== DATA PROVIDER INDONESIA ==========
$providers = [
    // Telkomsel (Halo, Simpati, Kartu As)
    '0811' => 'Telkomsel', '0812' => 'Telkomsel', '0813' => 'Telkomsel',
    '0821' => 'Telkomsel', '0822' => 'Telkomsel', '0823' => 'Telkomsel',
    '0851' => 'Telkomsel (By.U)', '0852' => 'Telkomsel', '0853' => 'Telkomsel',
    // Indosat (IM3, Mentari, Matrix)
    '0814' => 'Indosat', '0815' => 'Indosat', '0816' => 'Indosat',
    '0855' => 'Indosat', '0856' => 'Indosat', '0857' => 'Indosat', '0858' => 'Indosat',
    // XL Axiata (XL, Axis)
    '0817' => 'XL Axiata', '0818' => 'XL Axiata', '0819' => 'XL Axiata',
    '0859' => 'XL Axiata', '0877' => 'XL Axiata', '0878' => 'XL Axiata', '0879' => 'XL Axiata',
    // Tri (3)
    '0895' => 'Tri (3)', '0896' => 'Tri (3)', '0897' => 'Tri (3)', '0898' => 'Tri (3)', '0899' => 'Tri (3)',
    // Smartfren
    '0881' => 'Smartfren', '0882' => 'Smartfren', '0883' => 'Smartfren', '0884' => 'Smartfren',
    '0885' => 'Smartfren', '0886' => 'Smartfren', '0887' => 'Smartfren', '0888' => 'Smartfren', '0889' => 'Smartfren',
    // Bolt
    '0880' => 'Bolt'
];

// ========== NORMALISASI NOMOR ==========
$original_number = $number;
$number = preg_replace('/[^0-9]/', '', $number); // Hanya digit

// Konversi 08xx -> 628xx
if (substr($number, 0, 2) === '08') {
    $number = '62' . substr($number, 1);
}
// Jika tidak pakai 62, tambahkan
if (substr($number, 0, 2) !== '62') {
    $number = '62' . $number;
}

// Validasi panjang minimal (62 + minimal 8 digit = 10)
if (strlen($number) < 10 || strlen($number) > 15) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => 'Invalid phone number length. Must be 10-15 digits after country code.',
        'input' => ['number' => $original_number]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$country_code = substr($number, 0, 2);
$national_number = substr($number, 2);

// ========== DETEKSI PROVIDER ==========
$detected_provider = 'Unknown';
$prefix_4 = substr($national_number, 0, 4);
$prefix_3 = substr($national_number, 0, 3);
$prefix_2 = substr($national_number, 0, 2);

if (isset($providers[$prefix_4])) {
    $detected_provider = $providers[$prefix_4];
} elseif (isset($providers[$prefix_3])) {
    $detected_provider = $providers[$prefix_3];
} elseif (isset($providers[$prefix_2])) {
    $detected_provider = $providers[$prefix_2];
}

// Deteksi operator spesifik
$operator_category = 'Unknown';
if (strpos($detected_provider, 'Telkomsel') !== false) {
    $operator_category = 'GSM - Telkomsel Group';
} elseif (strpos($detected_provider, 'Indosat') !== false) {
    $operator_category = 'GSM - Indosat Ooredoo';
} elseif (strpos($detected_provider, 'XL') !== false) {
    $operator_category = 'GSM - XL Axiata';
} elseif (strpos($detected_provider, 'Tri') !== false) {
    $operator_category = 'GSM - Hutchison 3';
} elseif (strpos($detected_provider, 'Smartfren') !== false) {
    $operator_category = 'CDMA/LTE - Smartfren';
} elseif (strpos($detected_provider, 'Bolt') !== false) {
    $operator_category = 'LTE - Bolt';
}

// ========== TIPE NOMOR ==========
$first_digit = substr($national_number, 0, 1);
$number_type = 'Mobile';
if ($first_digit == '8') {
    $number_type = 'Mobile Phone';
} elseif ($first_digit == '2' || $first_digit == '3' || $first_digit == '4' || $first_digit == '5' || $first_digit == '6' || $first_digit == '7') {
    $number_type = 'Fixed-line';
}

// ========== FORMAT OUTPUT ==========
$international_format = '+' . $country_code . ' ' . chunk_split($national_number, 4, ' ');
$international_format = rtrim($international_format);
$national_format = '0' . $national_number;
$e164_format = '+' . $country_code . $national_number;

// ========== SUSUN RESPONSE ==========
$data = [
    'status' => true,
    'creator' => 'Tiyanz',
    'input' => ['number' => $original_number],
    'result' => [
        'informasi_dasar' => [
            'nomor_original' => $original_number,
            'nomor_normalisasi' => $number,
            'kode_negara' => '+' . $country_code,
            'nomor_nasional' => $national_number,
            'format_internasional' => $international_format,
            'format_nasional' => $national_format,
            'format_e164' => $e164_format,
            'valid' => true,
            'possible' => true,
            'dapat_dihubungi' => true
        ],
        'lokasi' => [
            'negara' => 'Indonesia',
            'negara_en' => 'Indonesia',
            'kode_negara_iso' => 'ID',
            'kode_area' => '62',
            'geografis' => ($number_type === 'Fixed-line')
        ],
        'jenis' => [
            'tipe' => $number_type,
            'is_mobile' => ($number_type === 'Mobile' || $number_type === 'Mobile Phone'),
            'is_fixed_line' => ($number_type === 'Fixed-line'),
            'kategori' => $operator_category
        ],
        'provider' => [
            'operator' => $detected_provider,
            'operator_en' => $detected_provider,
            'prefix' => substr($national_number, 0, 4)
        ],
        'zona_waktu' => [
            'timezone' => 'Asia/Jakarta',
            'timezones' => ['Asia/Jakarta', 'Asia/Pontianak', 'Asia/Makassar', 'Asia/Jayapura']
        ],
        'supported_providers' => [
            'Telkomsel (Simpati, Kartu As, Halo, By.U)',
            'Indosat (IM3, Mentari, Matrix)',
            'XL Axiata (XL, Axis)',
            'Tri (3)',
            'Smartfren',
            'Bolt'
        ]
    ]
];

// Cleanup keys
$keysToRemove = ['creator', 'Creator', 'author', 'Author'];
$data = removeKeysRecursive($data, $keysToRemove);
$data['creator'] = 'Tiyanz';

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ========== HELPER FUNCTION ==========
function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>