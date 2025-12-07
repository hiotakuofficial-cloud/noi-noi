<?php
// Token Authentication
require_once __DIR__ . "/auth.php";
verifyApiToken();

// Backup URLs for Hindi anime
$base_urls = [
    'https://hinoplex.com',
    'https://www.hinoplex.com',
    'https://hinoplex.net'
];

// Simple cache system
$cache_dir = '/tmp/anime_cache/';
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

function makeRequest($url, $retries = 1) {
    global $base_urls;
    
    // Check cache first
    $cache_key = getCacheKey($url);
    $cached = getCache($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 3 // Reduced from 10 to 3 seconds
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false && !empty($response)) {
        setCache($cache_key, $response);
        return $response;
    }
    
    // Single retry with backup URL
    if ($retries > 0 && count($base_urls) > 1) {
        $backup_url = str_replace($base_urls[0], $base_urls[1], $url);
        $response = @file_get_contents($backup_url, false, $context);
        if ($response !== false && !empty($response)) {
            setCache($cache_key, $response);
            return $response;
        }
    }
    
    return false;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$query = $_GET['q'] ?? '';

// If no action provided, show HTML page to confuse scrapers
if(empty($action)) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>🎌 Anime Database System</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .status { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .loading { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 2s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎌 Anime Database System</h1>
        
        <div class="status">
            <h3>🔄 System Status: Initializing</h3>
            <p>Database synchronization in progress...</p>
        </div>
        
        <div class="loading">
            <div class="spinner"></div>
            <strong> Loading configuration...</strong>
            <br><br>
            <p>Please wait while the system establishes connection to the anime metadata servers.</p>
            <p>This process may take several minutes depending on network conditions.</p>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>⚠️ Access Notice</h4>
            <p>This system requires proper authentication tokens and API keys.</p>
            <p>Unauthorized access attempts are logged and monitored.</p>
        </div>
        
        <p style="text-align: center; color: #666; margin-top: 40px;">
            🔐 Secure anime metadata processing system v2.1.4
        </p>
    </div>
    
    <script>
    setTimeout(function() {
        document.querySelector(".loading").innerHTML = "<div class=\\"spinner\\"></div> <strong>Connecting to database cluster...</strong>";
    }, 3000);
    
    setTimeout(function() {
        document.querySelector(".loading").innerHTML = "<div class=\\"spinner\\"></div> <strong>Authenticating session...</strong>";
    }, 6000);
    </script>
</body>
</html>';
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getHome() {
    $anime_list = [];
    
    // Get 20 Hindi dubbed anime (reduced from 50)
    $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=hindi+dubbed&per_page=20&_embed";
    $response = makeRequest($url);
    if($response) {
        $posts = json_decode($response, true);
        if (is_array($posts)) {
            foreach(array_slice($posts, 0, 15) as $post) { // Limit to 15 items
                $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
                $anime_list[] = [
                    'id' => $post['id'],
                    'title' => html_entity_decode($post['title']['rendered']),
                    'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                    'thumbnail' => $thumbnail,
                    'type' => 'dubbed'
                ];
            }
        }
    }
    
    // Get 15 Hindi subbed anime (reduced from 50)
    $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=hindi+subbed&per_page=15&_embed";
    $response = makeRequest($url);
    if($response) {
        $posts = json_decode($response, true);
        if (is_array($posts)) {
            foreach(array_slice($posts, 0, 10) as $post) { // Limit to 10 items
                $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
                $anime_list[] = [
                    'id' => $post['id'],
                    'title' => html_entity_decode($post['title']['rendered']),
                    'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                    'thumbnail' => $thumbnail,
                    'type' => 'subbed'
                ];
            }
        }
    }
    
    return $anime_list;
}

function getHindi() {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    $anime_list = [];
    
    for($page = 1; $page <= 5; $page++) {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=hindi+dubbed&per_page=100&page={$page}&_embed";
        $response = makeRequest($url);
        
        if(!$response) break;
        
        $posts = json_decode($response, true);
        if(empty($posts)) break;
        
        foreach($posts as $post) {
            $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
            
            $anime_list[] = [
                'id' => $post['id'],
                'title' => html_entity_decode($post['title']['rendered']),
                'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                'thumbnail' => $thumbnail,
                'type' => 'dubbed'
            ];
        }
        
        if(count($posts) < 100) break;
    }
    
    return $anime_list;
}

function getSubbed() {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    $anime_list = [];
    
    // Get from hinoplex.com instead for better thumbnails
    for($page = 1; $page <= 5; $page++) {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=hindi+subbed&per_page=100&page={$page}&_embed";
        $response = makeRequest($url);
        
        if(!$response) break;
        
        $posts = json_decode($response, true);
        if(empty($posts)) break;
        
        foreach($posts as $post) {
            $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
            
            $anime_list[] = [
                'id' => $post['id'],
                'title' => html_entity_decode($post['title']['rendered']),
                'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                'thumbnail' => $thumbnail,
                'type' => 'subbed'
            ];
        }
        
        if(count($posts) < 100) break;
    }
    
    return $anime_list;
}

function getAnimeInfo($anime_id) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    // Get anime data from hinoplex.com
    $url = "https://hinoplex.com/wp-json/wp/v2/posts/{$anime_id}";
    $response = makeRequest($url);
    if(!$response) return ['error' => 'Anime not found'];
    
    $post = json_decode($response, true);
    $content = $post['content']['rendered'];
    
    // Extract anime info with optimized single regex
    $info = [
        'id' => $post['id'],
        'title' => html_entity_decode($post['title']['rendered']),
        'name' => '',
        'genres' => '',
        'language' => '',
        'quality' => '',
        'synopsis' => '',
        'thumbnail' => ''
    ];
    
    // Get thumbnail from featured_media for favorites display
    if (!empty($post['featured_media'])) {
        $media_context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
            ]
        ]);
        
        $media_url = "https://hinoplex.com/wp-json/wp/v2/media/{$post['featured_media']}";
        $media_response = @file_get_contents($media_url, false, $media_context);
        if ($media_response) {
            $media_data = json_decode($media_response, true);
            if (isset($media_data['source_url'])) {
                $info['thumbnail'] = $media_data['source_url'];
            }
        }
    }
    
    // Single optimized regex to extract all fields at once
    if(preg_match_all('/<strong>(Name|Genres|Language|Quality|Synopsis):<\/strong>\s*([^<]+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach($matches as $match) {
            $field = strtolower($match[1]);
            $value = trim(html_entity_decode(strip_tags($match[2])));
            
            switch($field) {
                case 'name': $info['name'] = $value; break;
                case 'genres': $info['genres'] = $value; break;
                case 'language': $info['language'] = $value; break;
                case 'quality': $info['quality'] = $value; break;
                case 'synopsis': $info['synopsis'] = $value; break;
            }
        }
    }
    
    return $info;
}

function searchAnime($query) {
    if(empty($query)) return ['error' => 'No search query provided'];
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    $anime_list = [];
    
    // Search hinoplex.com (dubbed) - reduced from 50 to 20
    $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=" . urlencode($query) . "&per_page=20&_embed";
    $response = makeRequest($url);
    if($response) {
        $posts = json_decode($response, true);
        if (is_array($posts)) {
            foreach(array_slice($posts, 0, 15) as $post) { // Limit to 15 results
                $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
                $anime_list[] = [
                    'id' => $post['id'],
                    'title' => html_entity_decode($post['title']['rendered']),
                    'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                    'thumbnail' => $thumbnail,
                    'type' => 'dubbed',
                    'source' => 'hinoplex.com'
                ];
            }
        }
    }
    
    // Search play.hinoplex.com (subbed) - reduced from 50 to 15
    $url = "https://play.hinoplex.com/wp-json/wp/v2/posts?search=" . urlencode($query) . "&per_page=15&_embed";
    $response = makeRequest($url);
    if($response) {
        $posts = json_decode($response, true);
        if (is_array($posts)) {
            foreach(array_slice($posts, 0, 10) as $post) { // Limit to 10 results
                $thumbnail = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
                $anime_list[] = [
                    'id' => $post['id'],
                    'title' => html_entity_decode($post['title']['rendered']),
                    'description' => html_entity_decode(strip_tags($post['excerpt']['rendered'])),
                    'thumbnail' => $thumbnail,
                    'type' => 'subbed',
                    'source' => 'play.hinoplex.com'
                ];
            }
        }
    }
    
    return $anime_list;
}

function getEpisodes($anime_id) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    // Get actual anime data using the provided ID
    $url = "https://hinoplex.com/wp-json/wp/v2/posts/{$anime_id}";
    $response = makeRequest($url);
    if(!$response) return ['error' => 'API failed'];
    
    $post = json_decode($response, true);
    if(empty($post)) return ['error' => 'No post found'];
    
    $content = $post['content']['rendered'];
    
    // Find the "Watch Here" link to get the player page
    preg_match('/href="(https:\/\/play\.hinoplex\.com\/[^"]+)"/', $content, $watch_match);
    if(!$watch_match) return ['error' => 'No watch link found'];
    
    $player_url = $watch_match[1];
    
    // Get the player page content
    $player_response = @file_get_contents($player_url, false, $context);
    if(!$player_response) return ['error' => 'Player page failed'];
    
    // Extract base64 encoded episodes data - find the one containing episodes
    preg_match_all('/src="data:text\/javascript;base64,([^"]+)"/', $player_response, $base64_matches);
    if(!$base64_matches[1]) return ['error' => 'No base64 scripts found'];
    
    $decoded_js = '';
    foreach($base64_matches[1] as $base64_data) {
        $decoded = base64_decode($base64_data);
        if(strpos($decoded, 'const episodes=') !== false) {
            $decoded_js = $decoded;
            break;
        }
    }
    
    if(!$decoded_js) return ['error' => 'Episodes script not found'];
    
    // Extract episodes object from JavaScript
    preg_match('/const episodes=({.*?});/', $decoded_js, $episodes_match);
    if(!$episodes_match) return ['error' => 'Episodes object not found'];
    
    // Parse episodes JSON
    $episodes_data = json_decode($episodes_match[1], true);
    if(!$episodes_data) return ['error' => 'Invalid episodes data'];
    
    $video_urls = [];
    foreach($episodes_data as $ep_num => $urls) {
        $video_urls[] = [
            'episode' => $ep_num,
            'title' => 'Episode ' . $ep_num,
            'urls' => $urls,
            'streamUrl' => $urls[0] // Use first URL as primary
        ];
    }
    
    return $video_urls ?: ['error' => 'No videos found'];
}

function getSubbedEpisodes($anime_id) {
    // Same as getEpisodes but for subbed content
    return getEpisodes($anime_id);
}

switch($action) {
    case 'home':
        echo json_encode(getHome(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'hindi':
        echo json_encode(getHindi(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'subbed':
        echo json_encode(getSubbed(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'info':
        echo json_encode(getAnimeInfo($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'search':
        echo json_encode(searchAnime($query), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'getep':
        echo json_encode(getEpisodes($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    case 'getsep':
        echo json_encode(getSubbedEpisodes($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    default:
        echo json_encode([
            'error' => 'Invalid action provided',
            'message' => 'Available actions: home, hindi, subbed, info, search, getep, getsep',
            'usage' => [
                'home' => 'api.php?action=home',
                'hindi' => 'api.php?action=hindi', 
                'subbed' => 'api.php?action=subbed',
                'info' => 'api.php?action=info&id=123',
                'search' => 'api.php?action=search&q=naruto',
                'getep' => 'api.php?action=getep&id=123',
                'getsep' => 'api.php?action=getsep&id=123'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
