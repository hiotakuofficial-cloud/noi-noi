<?php
// Cache system
$cache_dir = '/tmp/animix_cache/';

// Token Authentication
require_once __DIR__ . "/auth.php";
verifyApiToken();
if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);

function getCacheKey($url) {
    return md5($url);
}

function getCache($key) {
    global $cache_dir;
    $file = $cache_dir . $key;
    if (file_exists($file) && (time() - filemtime($file)) < 86400) { // 24h cache
        return file_get_contents($file);
    }
    return false;
}

function setCache($key, $data) {
    global $cache_dir;
    file_put_contents($cache_dir . $key, $data);
}

function makeRequest($url) {
    $cache_key = getCacheKey($url);
    $cached = getCache($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 3
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false && !empty($response)) {
        setCache($cache_key, $response);
        return $response;
    }
    
    return false;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$query = $_GET['q'] ?? '';

// HTML disguise page
if(empty($action)) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>🎌 AnimixStream API System</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; text-align: center; }
        .status { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎌 AnimixStream Database System</h1>
        <div class="status">
            <h3>🔄 System Status: Online</h3>
            <p>Hindi anime database synchronization active...</p>
        </div>
    </div>
</body>
</html>';
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function searchAnime($query) {
    $url = "https://animixstream.com/ajax/search_anime.php?query=" . urlencode($query);
    $response = makeRequest($url);
    if (!$response) return [];
    
    $data = json_decode($response, true);
    if (!is_array($data)) return [];
    
    // Convert to hindi.php format - NO LIMIT
    $results = [];
    foreach ($data as $anime) {
        $results[] = [
            'id' => (int)$anime['id'],
            'title' => $anime['title'] . ' Hindi Dubbed',
            'description' => 'Anime Info: Name: ' . $anime['title'] . ' Language: Hindi Dubbed (Official) Quality: FHD, HD, SD Synopsis: Watch ' . $anime['title'] . ' in Hindi dubbed version on AnimixStream.',
            'thumbnail' => $anime['poster'],
            'type' => 'dubbed',
            'source' => 'animixstream.com'
        ];
    }
    
    return $results;
}

function getHome() {
    $anime_list = [];
    $popular_searches = ["naruto", "dragon ball", "one piece", "attack on titan", "demon slayer", "jujutsu kaisen"];
    
    foreach ($popular_searches as $search_term) {
        $results = searchAnime($search_term);
        if (!empty($results)) {
            $anime_list = array_merge($anime_list, $results);
        }
        if (count($anime_list) >= 20) break;
    }
    
    return array_slice($anime_list, 0, 20);
}

function getHindi() {
    return getHome();
}

function getAnimeInfo($id) {
    // Get anime info from anime.php page
    $url = "https://animixstream.com/anime.php?id=" . $id;
    $response = makeRequest($url);
    if (!$response) return ['error' => 'Anime not found'];
    
    // Parse real data from HTML
    preg_match('/<title>([^-]*) - Watch in Hindi on AnimixStream<\/title>/i', $response, $title);
    preg_match('/<meta name="description" content="([^"]*)"[^>]*>/i', $response, $desc);
    preg_match('/<meta name="keywords" content="[^"]*,\s*([^"]*)"[^>]*>/i', $response, $genre);
    
    $anime_title = isset($title[1]) ? trim($title[1]) : 'Unknown';
    $synopsis = isset($desc[1]) ? trim($desc[1]) : '';
    $genres = isset($genre[1]) ? trim($genre[1]) : '';
    
    return [
        'id' => (int)$id,
        'title' => $anime_title,
        'name' => $anime_title,
        'genres' => $genres,
        'language' => 'Hindi Dubbed (Official)',
        'quality' => 'FHD, HD, SD',
        'synopsis' => $synopsis,
        'thumbnail' => ''
    ];
}

function resolveShortUrl($url) {
    // Resolve short URLs to get final destination
    if (strpos($url, 'short.icu') !== false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'follow_location' => 0,
                'timeout' => 3
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        if ($headers && isset($headers['Location'])) {
            return is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
        }
    }
    
    return $url;
}

function getEpisodes($id) {
    // Get episodes from watch.php page
    $url = "https://animixstream.com/watch.php?id=" . $id;
    $response = makeRequest($url);
    if (!$response) return [];
    
    $episodes = [];
    
    // Look for episode links with episode parameter
    preg_match_all('/watch\.php\?id=' . $id . '&episode=(\d+)/', $response, $episode_matches);
    
    if (!empty($episode_matches[1])) {
        $episode_numbers = array_unique($episode_matches[1]);
        sort($episode_numbers);
        
        // Create episode list without loading each page
        foreach ($episode_numbers as $ep_num) {
            $episodes[] = [
                'episode' => str_pad($ep_num, 2, '0', STR_PAD_LEFT),
                'title' => 'Episode ' . str_pad($ep_num, 2, '0', STR_PAD_LEFT),
                'id' => $id,
                'episode_id' => $ep_num
            ];
        }
    } else {
        // Single episode
        $episodes[] = [
            'episode' => '01',
            'title' => 'Episode 01',
            'id' => $id,
            'episode_id' => '1'
        ];
    }
    
    return $episodes;
}

function playEpisode($id, $episode_id) {
    // Load specific episode and get video URLs
    $url = "https://animixstream.com/watch.php?id=" . $id . "&episode=" . $episode_id;
    $response = makeRequest($url);
    if (!$response) return ['error' => 'Episode not found'];
    
    $video_urls = [];
    
    // Extract video URLs from episode page
    preg_match_all('/https?:\/\/[^"\']*(?:filemoon|streamtape|doodstream|mixdrop|upstream|vidoza|short\.icu|abysscdn|mp4upload|streamwish|voe\.sx)\.(?:to|com|net|org|icu|sx)[^"\']*/', $response, $url_matches);
    
    if (!empty($url_matches[0])) {
        $video_urls = array_unique($url_matches[0]);
        
        // Resolve short URLs
        $resolved_urls = [];
        foreach ($video_urls as $video_url) {
            $resolved_urls[] = resolveShortUrl($video_url);
        }
        $video_urls = $resolved_urls;
    }
    
    return [
        'episode' => str_pad($episode_id, 2, '0', STR_PAD_LEFT),
        'title' => 'Episode ' . str_pad($episode_id, 2, '0', STR_PAD_LEFT),
        'urls' => $video_urls ?: [$url],
        'streamUrl' => !empty($video_urls) ? $video_urls[0] : $url
    ];
}

// Handle requests
switch ($action) {
    case 'home':
        echo json_encode(getHome(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        break;
        
    case 'hindi':
        echo json_encode(getHindi(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        break;
        
    case 'search':
        if (empty($query)) {
            echo json_encode(['error' => 'Query parameter required'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(searchAnime($query), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'info':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(getAnimeInfo($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'getep':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(getEpisodes($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'playep':
        $episode_id = $_GET['ep'] ?? '';
        if (empty($id) || empty($episode_id)) {
            echo json_encode(['error' => 'ID and ep parameters required'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(playEpisode($id, $episode_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    default:
        echo json_encode([
            'error' => 'Invalid action provided',
            'message' => 'Available actions: home, hindi, info, search, getep, playep'
        ], JSON_PRETTY_PRINT);
}
?>
