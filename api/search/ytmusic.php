<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Nanzz API - YouTube Music Search (Official YT Music API)
// Contoh: {"q": "lovely"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param q Kata Kunci Pencarian

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ========== CREDIT ==========
$credit = ['creator' => 'Nanzz'];
set_time_limit(20);

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    $data = ['status' => false, 'creator' => 'Nanzz', 'input' => ['q' => null], 'result' => ['msg' => 'Parameter q diperlukan']];
    echo json_encode(removeKeysRecursive($data, ['creator','Creator','author','Author']) + ['creator' => 'Nanzz'], JSON_PRETTY_PRINT);
    exit;
}

function ytMusicApi($endpoint, $body) {
    $ch = curl_init('https://music.youtube.com/youtubei/v1/' . $endpoint . '?prettyPrint=false');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
            'Origin: https://music.youtube.com',
            'Referer: https://music.youtube.com/',
            'Accept-Language: id-ID,id;q=0.9,en;q=0.8',
        ],
        CURLOPT_POSTFIELDS => json_encode(array_merge([
            'context' => [
                'client' => ['clientName' => 'WEB_REMIX', 'clientVersion' => '1.20260620.07.01', 'hl' => 'id', 'gl' => 'ID'],
                'user' => ['lockedSafetyMode' => false],
                'request' => ['useSsl' => true],
            ]
        ], $body)),
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

function getRunsText($runs) {
    $t = ''; foreach ($runs ?? [] as $r) $t .= $r['text'] ?? ''; return $t;
}

function getThumbnail($thumbObj) {
    $thumbs = $thumbObj['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? $thumbObj['thumbnails'] ?? [];
    return !empty($thumbs) ? end($thumbs)['url'] : '';
}

function parseMusicItem($r) {
    $cols = $r['flexColumns'] ?? [];
    $text1 = getRunsText($cols[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []);
    $text2 = getRunsText($cols[1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []);
    $text3 = getRunsText($cols[2]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []);
    $thumb = getThumbnail($r['thumbnail'] ?? []);
    
    $nav = $r['navigationEndpoint'] ?? [];
    $videoId = $nav['watchEndpoint']['videoId'] ?? ($r['playlistItemData']['videoId'] ?? '');
    $browseId = $nav['browseEndpoint']['browseId'] ?? '';
    
    if (stripos($text2, 'Artis') !== false || stripos($text2, 'subscriber') !== false || stripos($text2, 'audiens') !== false) {
        return ['type' => 'artist', 'name' => $text1, 'info' => trim(str_replace(['Artis', ' • '], '', $text2)), 'browseId' => $browseId, 'thumbnail' => $thumb];
    } elseif (stripos($text2, 'Video') !== false) {
        return ['type' => 'video', 'videoId' => $videoId, 'title' => $text1, 'channel' => trim(str_replace(['Video', ' • '], '', $text2)), 'thumbnail' => $thumb];
    } else {
        return ['type' => 'song', 'videoId' => $videoId, 'title' => $text1, 'artist' => trim(str_replace(['Lagu', ' • '], '', $text2)), 'album' => $text3, 'thumbnail' => $thumb];
    }
}

// Search dengan limit lebih besar
$response = ytMusicApi('search', ['query' => $query, 'params' => 'EgWKAQIIAWoKEAkQBRAKEAMQBA%3D%3D']);

$topResult = null;
$songs = [];
$videos = [];
$artists = [];
$albums = [];
$playlists = [];

$tabs = $response['contents']['tabbedSearchResultsRenderer']['tabs'] ?? [];

foreach ($tabs as $tab) {
    $sections = $tab['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
    
    foreach ($sections as $section) {
        // Top Card
        $card = $section['musicCardShelfRenderer'] ?? null;
        if ($card) {
            $topResult = [
                'videoId' => $card['onTap']['watchEndpoint']['videoId'] ?? '',
                'title' => getRunsText($card['title']['runs'] ?? []),
                'subtitle' => getRunsText($card['subtitle']['runs'] ?? []),
                'thumbnail' => getThumbnail($card['thumbnail'] ?? []),
            ];
            // Items dalam card
            foreach ($card['contents'] ?? [] as $c) {
                $r = $c['musicResponsiveListItemRenderer'] ?? null;
                if ($r) {
                    $parsed = parseMusicItem($r);
                    $songs[] = $parsed;
                }
            }
        }
        
        // Shelf (list lagu)
        $shelf = $section['musicShelfRenderer'] ?? null;
        if ($shelf) {
            foreach ($shelf['contents'] ?? [] as $c) {
                $r = $c['musicResponsiveListItemRenderer'] ?? null;
                if ($r) {
                    $parsed = parseMusicItem($r);
                    if ($parsed['type'] === 'song') $songs[] = $parsed;
                    elseif ($parsed['type'] === 'video') $videos[] = $parsed;
                    elseif ($parsed['type'] === 'artist') $artists[] = $parsed;
                }
            }
        }
        
        // Item section
        $items = $section['itemSectionRenderer']['contents'] ?? [];
        foreach ($items as $c) {
            $r = $c['musicResponsiveListItemRenderer'] ?? null;
            if ($r) {
                $parsed = parseMusicItem($r);
                if ($parsed['type'] === 'song') $songs[] = $parsed;
                elseif ($parsed['type'] === 'video') $videos[] = $parsed;
                elseif ($parsed['type'] === 'artist') $artists[] = $parsed;
            }
        }
    }
}

$total = count($songs) + count($videos) + count($artists) + count($albums) + count($playlists);

$data = [
    'status' => true,
    'creator' => 'Nanzz',
    'input' => ['q' => $query],
    'result' => [
        'query' => $query,
        'total' => $total,
        'total_songs' => count($songs),
        'total_videos' => count($videos),
        'total_artists' => count($artists),
        'top_result' => $topResult,
        'songs' => $songs,
        'videos' => $videos,
        'artists' => $artists,
    ]
];

echo json_encode(removeKeysRecursive($data, ['creator','Creator','author','Author']) + ['creator' => 'Nanzz'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

function removeKeysRecursive($array, $keysToRemove) {
    if (!is_array($array)) return $array;
    foreach ($keysToRemove as $key) unset($array[$key]);
    foreach ($array as &$value) {
        if (is_array($value)) $value = removeKeysRecursive($value, $keysToRemove);
    }
    return $array;
}
?>