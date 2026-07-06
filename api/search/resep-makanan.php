<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Cookpad Recipe Scraper
// Contoh: {"q": "nasi goreng", "page": 1}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param page (1|2|3|4|5) Jumlah halaman (default: 1)

header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

$query = $_GET['q'] ?? '';
$maxPages = intval($_GET['page'] ?? 1);

// Validasi page
$allowedPages = [1, 2, 3, 4, 5];
if (!in_array($maxPages, $allowedPages)) {
    $maxPages = 1;
}

if (empty($query)) {
    echo json_encode([
        'status' => false,
        'creator' => 'Tiyanz',
        'message' => 'Parameter q wajib diisi'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Function fetch URL
function fetchUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($response)) {
        return $response;
    }
    return false;
}

// Function strip HTML
function stripHtml($html) {
    $text = preg_replace('/<[^>]+>/', ' ', $html);
    $text = str_replace(['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'], ['&', '<', '>', '"', "'"], $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Function parse recipes
function parseRecipes($html) {
    $recipes = [];
    
    // Split by recipe ID
    $parts = explode('<li id="recipe_', $html);
    array_shift($parts); // Remove first empty part
    
    foreach ($parts as $part) {
        // Extract ID
        preg_match('/^(\d+)/', $part, $idMatch);
        $id = $idMatch[1] ?? '';
        
        // Extract title
        preg_match('/block-link__main[^>]*>([\s\S]*?)<\/a>/', $part, $titleMatch);
        $title = $titleMatch ? stripHtml($titleMatch[1]) : '';
        
        // Extract URL
        preg_match('/href="(\/eng\/recipes\/\d+)"/', $part, $urlMatch);
        $url = $urlMatch ? 'https://cookpad.com' . $urlMatch[1] : '';
        
        // Extract ingredients
        preg_match('/line-clamp-2 break-words">([\s\S]*?)<\/div>/', $part, $ingMatch);
        $ingredients = [];
        if ($ingMatch) {
            $ingText = stripHtml($ingMatch[1]);
            $ingredients = array_filter(array_map('trim', explode('•', $ingText)));
        }
        
        // Extract time
        preg_match('/mise-icon-time[\s\S]*?mise-icon-text">(.*?)<\/span>/', $part, $timeMatch);
        $time = $timeMatch ? stripHtml($timeMatch[1]) : '';
        
        // Extract servings
        preg_match('/mise-icon-user[\s\S]*?mise-icon-text">(.*?)<\/span>/', $part, $servMatch);
        $servings = $servMatch ? stripHtml($servMatch[1]) : '';
        
        // Extract chef
        preg_match('/text-cookpad-gray-600 text-cookpad-12[^>]*>([\s\S]*?)<\/span>/', $part, $chefMatch);
        $chef = $chefMatch ? stripHtml($chefMatch[1]) : '';
        
        // Extract image
        preg_match('/src="(https:\/\/img-global\.cpcdn\.com\/recipes\/[^"]+\.jpg)"/', $part, $imgMatch);
        $image = $imgMatch ? $imgMatch[1] : '';
        
        if ($id && $title) {
            $recipes[] = [
                'id' => $id,
                'title' => $title,
                'url' => $url,
                'chef' => $chef,
                'time' => $time,
                'servings' => $servings,
                'ingredients' => array_values($ingredients),
                'image' => $image
            ];
        }
    }
    
    return $recipes;
}

// Function parse meta
function parseMeta($html) {
    preg_match('/text-cookpad-gray-500">\((\d+)\)<\/span>/', $html, $totalMatch);
    $total = $totalMatch ? intval($totalMatch[1]) : 0;
    return ['total' => $total];
}

// Main scraper
$encoded = urlencode($query);
$allRecipes = [];
$meta = ['total' => 0];

for ($page = 1; $page <= $maxPages; $page++) {
    $url = $page === 1
        ? "https://cookpad.com/eng/search/{$encoded}"
        : "https://cookpad.com/eng/search/{$encoded}?page={$page}";
    
    $html = fetchUrl($url);
    
    if (!$html) {
        continue;
    }
    
    if ($page === 1) {
        $meta = parseMeta($html);
    }
    
    $recipes = parseRecipes($html);
    $allRecipes = array_merge($allRecipes, $recipes);
    
    // Delay antar page
    if ($page < $maxPages) {
        sleep(1);
    }
}

echo json_encode([
    'creator' => 'Tiyanz',
    'status' => true,
    'result' => [
        'query' => $query,
        'total' => $meta['total'],
        'pages_scraped' => $maxPages,
        'count' => count($allRecipes),
        'recipes' => $allRecipes
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>