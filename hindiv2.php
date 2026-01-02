<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Token Authentication
require_once __DIR__ . "/auth.php";
verifyApiToken();

$cache_dir = '/tmp/hindi_cache/';
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
            'timeout' => 10
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
    <title>🎌 Hindi Anime API System</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; text-align: center; }
        .status { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎌 Hindi Anime Database System</h1>
        <div class="status">
            <h3>🔄 System Status: Online</h3>
            <p>Hindi anime database synchronization active...</p>
        </div>
    </div>
</body>
</html>';
    exit;
}

switch($action) {
    case 'home':
        $result = getHome();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        break;
    case 'hindi':
        $result = getHindi();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        break;
    case 'search':
        if (empty($query)) {
            echo json_encode(['error' => 'Query parameter required'], JSON_PRETTY_PRINT);
        } else {
            $result = searchAnime($query);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'info':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
        } else {
            $result = getAnimeInfo($id);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'getep':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
        } else {
            $result = getEpisodes($id);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
    case 'playep':
        $episode_id = $_GET['ep'] ?? '';
        if (empty($id) || empty($episode_id)) {
            echo json_encode(['error' => 'ID and ep parameters required'], JSON_PRETTY_PRINT);
        } else {
            $result = playEpisode($id, $episode_id);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;
    default:
        echo json_encode([
            'error' => 'Invalid action provided',
            'message' => 'Available actions: home, hindi, info, search, getep, playep'
        ], JSON_PRETTY_PRINT);
}

function getHome() {
    try {
        // Use hinoplex.com as primary source
        $url = "https://hinoplex.com/wp-json/wp/v2/posts?per_page=100&orderby=date&order=desc";
        $response = makeRequest($url);
        $posts = json_decode($response, true);
        
        $anime_list = [];
        foreach ($posts as $post) {
            // Get featured image
            $thumbnail = null;
            if (!empty($post['featured_media'])) {
                try {
                    $mediaUrl = "https://hinoplex.com/wp-json/wp/v2/media/" . $post['featured_media'];
                    $mediaResponse = makeRequest($mediaUrl);
                    $media = json_decode($mediaResponse, true);
                    $thumbnail = $media['source_url'] ?? null;
                } catch (Exception $e) {
                    $thumbnail = null;
                }
            }
            
            $anime_list[] = [
                'id' => $post['id'],
                'title' => strip_tags($post['title']['rendered']),
                'description' => strip_tags($post['excerpt']['rendered']),
                'thumbnail' => $thumbnail,
                'type' => 'subbed',
                'source' => 'hiotaku.app'
            ];
        }
        
        return $anime_list;
        
    } catch (Exception $e) {
        return ['error' => 'Failed to get home content'];
    }
}

function getHindi() {
    return getHome();
}

function searchAnime($query) {
    try {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts?search=" . urlencode($query) . "&per_page=100";
        $response = makeRequest($url);
        $posts = json_decode($response, true);
        
        $results = [];
        foreach ($posts as $post) {
            // Get featured image
            $thumbnail = null;
            if (!empty($post['featured_media'])) {
                try {
                    $mediaUrl = "https://hinoplex.com/wp-json/wp/v2/media/" . $post['featured_media'];
                    $mediaResponse = makeRequest($mediaUrl);
                    $media = json_decode($mediaResponse, true);
                    $thumbnail = $media['source_url'] ?? null;
                } catch (Exception $e) {
                    $thumbnail = null;
                }
            }
            
            $results[] = [
                'id' => $post['id'],
                'title' => strip_tags($post['title']['rendered']),
                'description' => strip_tags($post['excerpt']['rendered']),
                'thumbnail' => $thumbnail,
                'type' => 'subbed',
                'source' => 'hiotaku.app'
            ];
        }
        
        return $results;
        
    } catch (Exception $e) {
        return ['error' => 'Search failed'];
    }
}

function getAnimeInfo($id) {
    try {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts/" . $id;
        $response = makeRequest($url);
        $post = json_decode($response, true);
        
        $content = $post['content']['rendered'];
        
        // Extract anime info
        $info = [];
        if (preg_match('/<p><strong>Genre:\s*([^<]+)<\/strong>.*?<strong>Language:\s*([^<]+)<\/strong>.*?<strong>Quality:\s*([^<]+)<\/strong><\/p>/s', $content, $infoMatch)) {
            $info = [
                'genres' => trim(strip_tags($infoMatch[1])),
                'language' => trim(strip_tags($infoMatch[2])),
                'quality' => trim(strip_tags($infoMatch[3]))
            ];
        }
        
        // Get featured image
        $thumbnail = null;
        if (!empty($post['featured_media'])) {
            try {
                $mediaUrl = "https://hinoplex.com/wp-json/wp/v2/media/" . $post['featured_media'];
                $mediaResponse = makeRequest($mediaUrl);
                $media = json_decode($mediaResponse, true);
                $thumbnail = $media['source_url'] ?? null;
            } catch (Exception $e) {
                $thumbnail = null;
            }
        }
        
        return [
            'id' => (int)$id,
            'title' => strip_tags($post['title']['rendered']),
            'name' => strip_tags($post['title']['rendered']),
            'genres' => $info['genres'] ?? 'Action, Adventure',
            'language' => $info['language'] ?? 'Hindi Subbed',
            'quality' => $info['quality'] ?? 'FHD, HD, SD',
            'synopsis' => strip_tags($post['excerpt']['rendered']),
            'thumbnail' => $thumbnail
        ];
        
    } catch (Exception $e) {
        return ['error' => 'Failed to get anime info'];
    }
}

function getEpisodes($id) {
    try {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts/" . $id;
        $response = makeRequest($url);
        $post = json_decode($response, true);
        
        $content = $post['content']['rendered'];
        
        // Look for "Watch Here" links that lead to play.hinoplex.com
        preg_match_all('/href="(https:\/\/play\.hinoplex\.com\/[^"]+)"/', $content, $playMatches);
        
        // Extract episode numbers from content
        preg_match_all('/(?:episode|ep|e)\s*(\d+)/i', $content, $epMatches);
        $maxEpisode = 1;
        if (!empty($epMatches[1])) {
            $maxEpisode = max($epMatches[1]);
        }
        
        // Look for episode ranges like [1-12] or [24] or (12 Episodes)
        preg_match_all('/\[(\d+)(?:-(\d+))?\]|\((\d+)\s*(?:episodes?|eps?)\)/i', $post['title']['rendered'], $rangeMatches);
        if (!empty($rangeMatches[1])) {
            $maxEpisode = max($maxEpisode, (int)$rangeMatches[1][0]);
            if (!empty($rangeMatches[2][0])) {
                $maxEpisode = max($maxEpisode, (int)$rangeMatches[2][0]);
            }
        }
        if (!empty($rangeMatches[3])) {
            $maxEpisode = max($maxEpisode, (int)$rangeMatches[3][0]);
        }
        
        // Look for common anime episode patterns
        if (stripos($post['title']['rendered'], 'season') !== false) {
            $maxEpisode = max($maxEpisode, 24); // Default season length
        }
        
        // Generate episodes based on detected count or default
        $episodeCount = max($maxEpisode, 12);
        
        // Cap at reasonable limits
        if ($episodeCount > 2000) $episodeCount = 24;
        if ($episodeCount < 1) $episodeCount = 12;
        
        // Always generate full episode list
        $episodes = [];
        for ($i = 1; $i <= $episodeCount; $i++) {
            $watchUrl = null;
            // Try to find corresponding watch URL
            if (isset($playMatches[1][$i-1])) {
                $watchUrl = $playMatches[1][$i-1];
            }
            
            $episode = [
                'episode' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'title' => 'Episode ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'id' => (string)$id,
                'episode_id' => (string)$i
            ];
            
            if ($watchUrl) {
                $episode['watch_url'] = $watchUrl;
            }
            
            $episodes[] = $episode;
        }
        
        return $episodes;
        
    } catch (Exception $e) {
        return ['error' => 'Failed to get episodes'];
    }
}

function playEpisode($id, $episode_id) {
    try {
        $url = "https://hinoplex.com/wp-json/wp/v2/posts/" . $id;
        $response = makeRequest($url);
        $post = json_decode($response, true);
        
        $content = $post['content']['rendered'];
        
        // Look for "Watch Here" links
        preg_match_all('/href="(https:\/\/play\.hinoplex\.com\/[^"]+)"/', $content, $playMatches);
        
        $video_urls = [];
        
        // Try to get the specific episode URL
        if (!empty($playMatches[1])) {
            $play_url = $playMatches[1][0]; // Fixed: use first match properly
            
            // Get the player page
            $player_response = makeRequest($play_url);
            if ($player_response) {
                // Look for common video hosting iframes
                $iframe_patterns = [
                    '/src="(https:\/\/[^"]*(?:streamtape|doodstream|mixdrop|upstream|vidoza|streamlare|filemoon)[^"]*)"/',
                    '/src="(https:\/\/[^"]*(?:embed|player)[^"]*)"/',
                    '/data-src="([^"]*)"/',
                    '/"(https:\/\/[^"]*\.m3u8[^"]*)"/',
                    '/"(https:\/\/[^"]*(?:mp4|mkv|avi)[^"]*)"/'
                ];
                
                foreach ($iframe_patterns as $pattern) {
                    preg_match_all($pattern, $player_response, $matches);
                    foreach ($matches[1] as $match_url) {
                        if (strpos($match_url, 'http') === 0 && 
                            !strpos($match_url, 'googletagmanager') && 
                            !strpos($match_url, 'jquery') &&
                            !strpos($match_url, '.js') &&
                            !strpos($match_url, '.css')) {
                            $video_urls[] = $match_url;
                        }
                    }
                }
                
                // Extract base64 encoded URLs from JavaScript
                preg_match_all('/atob\(["\']([^"\']+)["\']\)/', $player_response, $base64_matches);
                foreach ($base64_matches[1] as $encoded) {
                    $decoded = base64_decode($encoded);
                    if (strpos($decoded, 'http') === 0) {
                        $video_urls[] = $decoded;
                    }
                }
                
                // Look for base64 in data attributes
                preg_match_all('/data-[^=]*="([A-Za-z0-9+\/=]{50,})"/', $player_response, $data_base64_matches);
                foreach ($data_base64_matches[1] as $encoded) {
                    $decoded = base64_decode($encoded);
                    if (strpos($decoded, 'http') === 0) {
                        $video_urls[] = $decoded;
                    }
                }
                
                // Look for base64 in script src
                preg_match_all('/src="data:text\/javascript;base64,([^"]+)"/', $player_response, $script_base64_matches);
                foreach ($script_base64_matches[1] as $encoded) {
                    $decoded = base64_decode($encoded);
                    // Look for URLs in decoded JavaScript
                    preg_match_all('/(https?:\/\/[^\s"\'<>]+)/', $decoded, $url_matches);
                    foreach ($url_matches[1] as $url) {
                        $video_urls[] = $url;
                    }
                }
                
                // Look for any base64 strings that might contain URLs
                preg_match_all('/[A-Za-z0-9+\/=]{100,}/', $player_response, $long_base64_matches);
                foreach ($long_base64_matches[0] as $encoded) {
                    $decoded = base64_decode($encoded);
                    if ($decoded && strpos($decoded, 'http') !== false) {
                        preg_match_all('/(https?:\/\/[^\s"\'<>]+)/', $decoded, $url_matches);
                        foreach ($url_matches[1] as $url) {
                            $video_urls[] = $url;
                        }
                    }
                }
                
                // Look for URLs in any JavaScript variables
                preg_match_all('/(?:src|url|link|video|stream)\s*[:=]\s*["\']([^"\']+)["\']/', $player_response, $js_var_matches);
                foreach ($js_var_matches[1] as $url) {
                    if (strpos($url, 'http') === 0) {
                        $video_urls[] = $url;
                    }
                }
                
                // Look for URLs in HTML comments
                preg_match_all('/<!--.*?(https?:\/\/[^\s]+).*?-->/', $player_response, $comment_matches);
                foreach ($comment_matches[1] as $url) {
                    $video_urls[] = $url;
                }
                
                // Look for any HTTP URLs in the page
                preg_match_all('/(https?:\/\/[^\s"\'<>]+)/', $player_response, $all_url_matches);
                foreach ($all_url_matches[1] as $url) {
                    $video_urls[] = $url;
                }
            }
        }
        
        // Remove duplicates and filter out non-video URLs
        $video_urls = array_unique($video_urls);
        $filtered_urls = [];
        
        foreach ($video_urls as $url) {
            // More comprehensive filtering for video URLs
            if ((strpos($url, 'embed') !== false || 
                strpos($url, 'player') !== false ||
                strpos($url, '.m3u8') !== false ||
                strpos($url, 'stream') !== false ||
                strpos($url, 'video') !== false ||
                strpos($url, 'watch') !== false ||
                preg_match('/\.(mp4|mkv|avi|webm|flv)/', $url) ||
                preg_match('/(streamtape|doodstream|mixdrop|upstream|vidoza|streamlare|filemoon|smoothpre|voe|streamhub)/', $url)) &&
                // Exclude non-video URLs
                strpos($url, 'oembed') === false &&
                strpos($url, 'wp-json') === false &&
                strpos($url, 'wp-content') === false &&
                strpos($url, '.css') === false &&
                strpos($url, '.js') === false &&
                strpos($url, 'googletagmanager') === false) {
                $filtered_urls[] = $url;
            }
        }
        
        // Only return real extracted URLs, no fallbacks
        if (empty($filtered_urls)) {
            return ['error' => 'No video sources found'];
        }
        
        return [
            'episode' => str_pad($episode_id, 2, '0', STR_PAD_LEFT),
            'title' => 'Episode ' . str_pad($episode_id, 2, '0', STR_PAD_LEFT),
            'urls' => $filtered_urls,
            'streamUrl' => $filtered_urls[0] ?? ''
        ];
        
    } catch (Exception $e) {
        return ['error' => 'Failed to get episode'];
    }
}
?>
