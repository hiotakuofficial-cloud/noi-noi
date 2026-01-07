<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'auth.php';
verifyApiToken();

$cache_dir = '/tmp/animesalt_cache/';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);

function makeRequest($url) {
    $cache_key = md5($url);
    $cache_file = $GLOBALS['cache_dir'] . $cache_key;
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
        return file_get_contents($cache_file);
    }
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 300
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        @file_put_contents($cache_file, $response);
        return $response;
    }
    
    return false;
}

$action = $_GET['action'] ?? '';

if (empty($action)) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><head><title>AnimeSalt Hindi API</title></head><body><h1>System Online</h1></body></html>';
    exit;
}

switch($action) {
    case 'home':
    case 'hindi':
        echo json_encode(getHindi(), JSON_PRETTY_PRINT);
        break;
    case 'search':
        $query = $_GET['q'] ?? '';
        echo json_encode(searchAnime($query), JSON_PRETTY_PRINT);
        break;
    case 'info':
        $id = $_GET['id'] ?? '';
        echo json_encode(getAnimeInfo($id), JSON_PRETTY_PRINT);
        break;
    case 'getep':
        $id = $_GET['id'] ?? '';
        echo json_encode(getEpisodes($id), JSON_PRETTY_PRINT);
        break;
    case 'playep':
        $id = $_GET['id'] ?? '';
        $ep = $_GET['ep'] ?? '';
        echo json_encode(playEpisode($id, $ep), JSON_PRETTY_PRINT);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getHindi() {
    $anime_list = [];
    
    // Scrape first 20 pages to get more content
    for ($page = 1; $page <= 20; $page++) {
        $url = $page == 1 ? 'https://animesalt.top/category/language/hindi/' : 'https://animesalt.top/category/language/hindi/page/' . $page . '/';
        $html = makeRequest($url);
        
        if (!$html) continue;
        
        // Extract anime posts
        preg_match_all('/<li[^>]*class="[^"]*post-(\d+)[^"]*"[^>]*>.*?<h2[^>]*class="entry-title"[^>]*>([^<]+)<\/h2>.*?data-src="([^"]*)"[^>]*alt="[^"]*"[^>]*class="lazyload".*?<a href="([^"]*)"[^>]*class="lnk-blk"><\/a>/s', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $thumbnail = $match[3];
            if (strpos($thumbnail, '//') === 0) {
                $thumbnail = 'https:' . $thumbnail;
            }
            
            $anime_list[] = [
                'id' => $match[1],
                'title' => trim($match[2]),
                'description' => 'Hindi anime content',
                'thumbnail' => $thumbnail,
                'type' => strpos($match[4], '/series/') !== false ? 'series' : 'subbed',
                'source' => 'hiotaku.app'
            ];
        }
        
        // Stop if we have enough content
        if (count($anime_list) >= 200) break;
    }
    
    return $anime_list;
}

function searchAnime($query) {
    if (empty($query)) return ['error' => 'Query required'];
    
    $html = makeRequest('https://animesalt.top/?s=' . urlencode($query));
    if (!$html) return ['error' => 'Search failed'];
    
    $results = [];
    
    preg_match_all('/<li[^>]*class="[^"]*post-(\d+)[^"]*"[^>]*>.*?<h2[^>]*class="entry-title"[^>]*>([^<]+)<\/h2>.*?data-src="([^"]*)"[^>]*alt="[^"]*"[^>]*class="lazyload".*?<a href="([^"]*)"[^>]*class="lnk-blk"><\/a>/s', $html, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $thumbnail = $match[3];
        if (strpos($thumbnail, '//') === 0) {
            $thumbnail = 'https:' . $thumbnail;
        }
        
        $results[] = [
            'id' => $match[1],
            'title' => trim($match[2]),
            'description' => 'Search result',
            'thumbnail' => $thumbnail,
            'type' => 'subbed',
            'source' => 'hiotaku.app'
        ];
    }
    
    return $results;
}

function getAnimeInfo($id) {
    $html = makeRequest('https://animesalt.top/?p=' . $id);
    if (!$html) return ['error' => 'Anime not found'];
    
    $info = [];
    
    // Extract title from title tag
    if (preg_match('/<title>([^<]+)<\/title>/', $html, $match)) {
        $title = trim(strip_tags($match[1]));
        // Remove all variations of "Watch Now" text
        $title = preg_replace('/ - Watch Now.*$/', '', $title);
        $title = str_replace(' - Anime Salt', '', $title);
        $info['title'] = $title;
    }
    
    // Extract real synopsis from overview section
    if (preg_match('/<div id="overview-text"[^>]*>(.*?)<\/div>/s', $html, $match)) {
        $synopsis = strip_tags($match[1]);
        $synopsis = trim(preg_replace('/\s+/', ' ', $synopsis));
        $info['synopsis'] = $synopsis;
    } elseif (preg_match('/<meta name="description" content="([^"]*)"/', $html, $match)) {
        $synopsis = html_entity_decode($match[1]);
        $info['synopsis'] = $synopsis;
    }
    
    // Extract genres and languages from links
    $genres = [];
    $languages = [];
    
    if (preg_match_all('/>([^<]*)<\/a>/', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $item = trim($match);
            if (in_array(strtolower($item), ['action', 'adventure', 'fantasy', 'shounen', 'comedy', 'drama', 'romance', 'sci-fi', 'thriller'])) {
                $genres[] = $item;
            } elseif (in_array(strtolower($item), ['hindi', 'english', 'japanese', 'tamil', 'telugu', 'malayalam', 'bengali'])) {
                $languages[] = $item;
            }
        }
    }
    
    // Extract poster
    if (preg_match('/data-src="([^"]*)"[^>]*alt="[^"]*"[^>]*class="lazyload"/', $html, $match)) {
        $info['thumbnail'] = $match[1];
        if (strpos($info['thumbnail'], '//') === 0) {
            $info['thumbnail'] = 'https:' . $info['thumbnail'];
        }
    }
    
    // Extract episode count for episodes field
    $episodeCount = 'Unknown';
    if (preg_match('/(\d+)\s*Episodes?/i', $html, $match)) {
        $episodeCount = $match[1] . ' Episodes';
    }
    
    // Determine type based on URL structure
    $type = 'TV Series';
    if (preg_match('/canonical" href="https:\/\/animesalt\.top\/movies\//', $html)) {
        $type = 'Movie';
    }
    
    return [
        'id' => (int)$id,
        'title' => $info['title'] ?? 'Unknown Anime',
        'name' => $info['title'] ?? 'Unknown Anime',
        'genres' => !empty($genres) ? $genres : ['Action', 'Adventure'], // Return as array
        'language' => !empty($languages) ? implode(', ', $languages) : 'Hindi Subbed',
        'quality' => '480p, 720p, 1080p',
        'synopsis' => $info['synopsis'] ?? 'Anime series description',
        'description' => $info['synopsis'] ?? 'Anime series description', // Add description for Flutter app
        'thumbnail' => $info['thumbnail'] ?? null,
        'year' => 'Unknown', // Site doesn't have structured year data
        'status' => 'Completed', // Default status
        'episodes' => $episodeCount, // Real episode count from site
        'duration' => 'Unknown', // Site doesn't have duration data
        'studio' => 'Unknown', // Site doesn't have studio data
        'type' => $type, // Movie or TV Series based on URL
        'rating' => 'Unknown' // Site doesn't have rating data
    ];
}

function getEpisodes($id) {
    $html = makeRequest('https://animesalt.top/?p=' . $id);
    if (!$html) return ['error' => 'Episodes not found'];
    
    $episodes = [];
    $episodeCount = 12; // Default
    
    // Check if it's a movie by checking canonical URL
    $isMovie = false;
    if (preg_match('/canonical" href="https:\/\/animesalt\.top\/movies\//', $html)) {
        $isMovie = true;
    }
    
    if ($isMovie) {
        // For movies, return single episode
        $episodes[] = [
            'episode' => '01',
            'title' => 'Movie',
            'id' => (string)$id,
            'episode_id' => '1',
            'watch_url' => 'https://animesalt.top/movies/' . $id . '/'
        ];
        return $episodes;
    }
    
    // Extract real episode count from the page for series
    if (preg_match('/(\d+)\s*Episodes?/i', $html, $match)) {
        $episodeCount = (int)$match[1];
    }
    
    // No limits - return all episodes
    if ($episodeCount < 1) $episodeCount = 12;
    
    // Generate episodes based on real count
    for ($i = 1; $i <= $episodeCount; $i++) {
        $episodes[] = [
            'episode' => str_pad($i, 2, '0', STR_PAD_LEFT),
            'title' => 'Episode ' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'id' => (string)$id,
            'episode_id' => (string)$i,
            'watch_url' => 'https://animesalt.top/episode/' . $id . '-' . $i . '/'
        ];
    }
    
    return $episodes;
}

function playEpisode($id, $episode_id) {
    // Get anime slug from the anime page first
    $animeHtml = makeRequest('https://animesalt.top/?p=' . $id);
    $animeSlug = '';
    $isMovie = false;
    
    if ($animeHtml) {
        if (preg_match('/canonical" href="https:\/\/animesalt\.top\/movies\/([^"\/]+)/', $animeHtml, $match)) {
            $animeSlug = $match[1];
            $isMovie = true;
        } elseif (preg_match('/canonical" href="https:\/\/animesalt\.top\/series\/([^"\/]+)/', $animeHtml, $match)) {
            $animeSlug = $match[1];
        }
    }
    
    if (empty($animeSlug)) {
        return ['error' => 'Anime not found'];
    }
    
    $html = '';
    
    if ($isMovie) {
        // For movies, get the movie page directly
        $movieUrl = "https://animesalt.top/movies/" . $animeSlug . "/";
        $html = makeRequest($movieUrl);
    } else {
        // For series, try episode URL with season format
        $episodeUrl = "https://animesalt.top/episode/" . $animeSlug . "-1x" . $episode_id . "/";
        $html = makeRequest($episodeUrl);
    }
    
    if (!$html) {
        return ['error' => 'Episode not found'];
    }
    
    $video_urls = [];
    
    // Look for iframe video sources
    $patterns = [
        '/src="(https:\/\/[^"]*(?:zephyrflick|awstream|embed|player)[^"]*)"/',
        '/data-src="(https:\/\/[^"]*(?:zephyrflick|awstream|embed|player)[^"]*)"/',
        '/src="([^"]*\.m3u8[^"]*)"/',
        '/"(https:\/\/[^"]*(?:streamtape|doodstream|mixdrop)[^"]*)"/'
    ];
    
    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $html, $matches);
        foreach ($matches[1] as $url) {
            if (strpos($url, 'http') === 0) {
                $video_urls[] = $url;
            }
        }
    }
    
    return [
        'episode' => str_pad($episode_id, 2, '0', STR_PAD_LEFT),
        'title' => $isMovie ? 'Movie' : 'Episode ' . str_pad($episode_id, 2, '0', STR_PAD_LEFT),
        'urls' => array_unique($video_urls),
        'streamUrl' => $video_urls[0] ?? ''
    ];
}
?>
