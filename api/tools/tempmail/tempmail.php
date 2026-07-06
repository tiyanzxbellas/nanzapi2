<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Temp-Mail.app Generator & Inbox
// Contoh: {"mode": "generate"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param mode (generate|inbox) Pilih mode
// @param id Visitor ID (untuk mode inbox)

header('Content-Type: application/json; charset=utf-8');

// ========== CREDIT ==========
$credit = [
    'creator' => 'Tiyanz'
];

$mode = $_GET['mode'] ?? 'generate';
$visitorId = $_GET['id'] ?? '';

$allowedModes = ['generate', 'inbox'];
if (!in_array($mode, $allowedModes)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Mode tidak valid. Pilih: generate, inbox'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($mode === 'inbox' && empty($visitorId)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter id (visitor_id) wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('BASE_URL', 'https://temp-mail.app/api');
define('UA', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 Chrome/148.0.0.0 Mobile Safari/537.36');

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function curlTempMail($endpoint, $visitorId = '', $params = []) {
    $url = BASE_URL . $endpoint;
    if (!empty($params)) $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $headers = [
        'User-Agent: ' . UA,
        'Accept: */*',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Referer: https://temp-mail.app/'
    ];
    
    if (!empty($visitorId)) {
        $headers[] = 'visitor-id: ' . $visitorId;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $http_code, 'data' => json_decode($response, true)];
}

try {
    // ========== GENERATE ==========
    if ($mode === 'generate') {
        $vid = generateUUID();
        $result = curlTempMail('/mail/address', $vid, [
            'refresh' => 'false',
            'expire' => 1440,
            'part' => 'main'
        ]);
        
        $data = $result['data'];
        
        if (!$data || empty($data['address'])) {
            $vid = generateUUID();
            $result = curlTempMail('/mail/address', $vid, [
                'refresh' => 'false',
                'expire' => 1440,
                'part' => 'main'
            ]);
            $data = $result['data'];
        }
        
        if (!$data || empty($data['address'])) throw new Exception('Gagal generate email');
        
        echo json_encode(array_merge($credit, [
            'status' => true,
            'result' => [
                'address' => $data['address'],
                'expire_minutes' => $data['expire'] ?? 1440,
                'remaining_minutes' => round(($data['remainingTime'] ?? 0) / 60),
                'visitor_id' => $vid
            ]
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    // ========== INBOX ==========
    if ($mode === 'inbox') {
        // Ambil email dulu
        $addrResult = curlTempMail('/mail/address', $visitorId, [
            'refresh' => 'false',
            'expire' => 1440,
            'part' => 'main'
        ]);
        
        $email = $addrResult['data']['address'] ?? '';
        
        // Ambil messages
        $result = curlTempMail('/mail/list', $visitorId, ['part' => 'main']);
        
        if ($result['code'] !== 200) throw new Exception('Gagal akses inbox (HTTP ' . $result['code'] . ')');
        
        $messages = $result['data']['message'] ?? [];
        
        $formatted = [];
        foreach ($messages as $msg) {
            $cleanContent = strip_tags($msg['content'] ?? '');
            $cleanContent = html_entity_decode($cleanContent, ENT_QUOTES, 'UTF-8');
            
            $formatted[] = [
                'id' => $msg['id'] ?? '',
                'from' => $msg['fromName'] ?? $msg['fromAddress'] ?? '',
                'subject' => $msg['subject'] ?? '(no subject)',
                'preview' => $msg['preview'] ?? '',
                'content' => trim($cleanContent),
                'date' => $msg['date'] ?? '',
                'is_read' => $msg['isRead'] ?? false
            ];
        }
        
        echo json_encode(array_merge($credit, [
            'status' => true,
            'result' => [
                'email' => $email,
                'total' => count($formatted),
                'messages' => $formatted
            ]
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => $e->getMessage()
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>