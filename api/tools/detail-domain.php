<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Domain RDAP Lookup
// Contoh: {"domain":"google.com"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param domain Nama domain

header('Content-Type: application/json; charset=utf-8');

$credit = [
    'creator' => 'Tiyanz'
];

$domain = $_GET['domain'] ?? '';

if (empty($domain)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Parameter domain wajib diisi'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = 'https://rdap.org/domain/' . urlencode($domain);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Request gagal (HTTP ' . $httpCode . ')'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode(array_merge($credit, [
        'status' => false,
        'message' => 'Response tidak valid'
    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$registrar = null;

if (!empty($data['entities'])) {
    foreach ($data['entities'] as $entity) {

        if (
            !empty($entity['roles']) &&
            in_array('registrar', $entity['roles'])
        ) {

            if (
                isset($entity['vcardArray'][1]) &&
                is_array($entity['vcardArray'][1])
            ) {

                foreach ($entity['vcardArray'][1] as $item) {

                    if (
                        isset($item[0]) &&
                        $item[0] === 'fn'
                    ) {
                        $registrar = $item[3] ?? null;
                        break 2;
                    }
                }
            }
        }
    }
}

$created = null;
$updated = null;
$expires = null;

if (!empty($data['events'])) {

    foreach ($data['events'] as $event) {

        if (($event['eventAction'] ?? '') === 'registration') {
            $created = $event['eventDate'] ?? null;
        }

        if (($event['eventAction'] ?? '') === 'last changed') {
            $updated = $event['eventDate'] ?? null;
        }

        if (($event['eventAction'] ?? '') === 'expiration') {
            $expires = $event['eventDate'] ?? null;
        }
    }
}

$result = [
    'domain' => $data['ldhName'] ?? null,
    'handle' => $data['handle'] ?? null,
    'status_domain' => $data['status'] ?? [],
    'nameservers' => array_map(function($v) {
        return $v['ldhName'] ?? null;
    }, $data['nameservers'] ?? []),
    'registrar' => $registrar,
    'created' => $created,
    'updated' => $updated,
    'expires' => $expires
];

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => $result,
    'message' => 'working'
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>