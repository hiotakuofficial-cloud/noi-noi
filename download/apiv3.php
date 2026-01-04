<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Token Authentication
require_once '../auth.php';
verifyApiToken();

// Simple cache system
$cache_dir = '/tmp/apiv3_cache/';
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

function scrapeAllEpisodes($animeUrl, $baseUrl) {
    // Check cache first
    $cache_key = getCacheKey($animeUrl . '_episodes');
    $cached = getCache($cache_key);
    if ($cached !== false) {
        return json_decode($cached, true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $animeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ApeleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) return ['error' => 'Failed to fetch anime page'];
    
    $episodes = [];
    
    // Extract total episodes count
    $totalEpisodes = 0;
    if (preg_match('/<b>Episodes:<\/b>\s*(\d+)/i', $html, $matches)) {
        $totalEpisodes = (int)$matches[1];
    }
    
    // Extract episode links - NO DOWNLOAD LINKS
    $animeSlug = basename($animeUrl, '/');
    if (preg_match_all('/href="([^"]*' . preg_quote($animeSlug) . '-episode-(\d+)[^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $episodes[] = [
                'episode' => (int)$match[2],
                'id' => basename($match[1], '/')
            ];
        }
    }
    
    // Sort episodes and remove duplicates
    $episodes = array_unique($episodes, SORT_REGULAR);
    usort($episodes, function($a, $b) {
        return $a['episode'] - $b['episode'];
    });
    
    $result = [
        'total_episodes' => $totalEpisodes,
        'available_episodes' => count($episodes),
        'episodes' => $episodes,
        'api' => 'apiv3.php'
    ];
    
    // Cache the result
    setCache($cache_key, json_encode($result));
    
    return $result;
}

function scrapeEpisodes($animeUrl, $baseUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $animeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ApeleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) return ['error' => 'Failed to fetch anime page'];
    
    $episodes = [];
    
    // Extract total episodes count
    $totalEpisodes = 0;
    if (preg_match('/<b>Episodes:<\/b>\s*(\d+)/i', $html, $matches)) {
        $totalEpisodes = (int)$matches[1];
    }
    
    // Extract episode links - more specific pattern
    $animeSlug = basename($animeUrl, '/');
    if (preg_match_all('/href="([^"]*' . preg_quote($animeSlug) . '-episode-(\d+)[^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $episodes[] = [
                'episode' => (int)$match[2],
                'id' => basename($match[1], '/')
            ];
        }
    }
    
    // Sort episodes by number and remove duplicates
    $episodes = array_unique($episodes, SORT_REGULAR);
    usort($episodes, function($a, $b) {
        return $a['episode'] - $b['episode'];
    });
    
    // Get download links for each episode (optional with limit)
    $withLinks = isset($_GET['links']) && $_GET['links'] === 'true';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default to 10 episodes to avoid timeout
    
    if ($withLinks) {
        set_time_limit(300); // 5 minutes timeout
        $count = 0;
        foreach ($episodes as &$episode) {
            if ($count >= $limit) break;
            
            $episodeUrl = $baseUrl . '/' . $episode['id'] . '/';
            $downloadData = scrapeDownloadLinks($episodeUrl);
            
            $episode['links'] = isset($downloadData['links']) ? $downloadData['links'] : [];
            $count++;
        }
    }
    
    return [
        'total_episodes' => $totalEpisodes,
        'available_episodes' => count($episodes),
        'episodes' => $episodes,
        'api' => 'apiv3.php'
    ];
}

function scrapeDownloadLinks($episodeUrl) {
    // Check cache first
    $cache_key = getCacheKey($episodeUrl . '_download');
    $cached = getCache($cache_key);
    if ($cached !== false) {
        return json_decode($cached, true);
    }
    
    // First get the page to establish session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $episodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) return ['error' => 'Failed to fetch episode page'];
    
    // Extract parameters
    $malId = '';
    $episode = '';
    $nonce = '';
    
    if (preg_match("/var mal_id = '([^']+)'/", $html, $matches)) {
        $malId = $matches[1];
    }
    
    if (preg_match("/var ep = '([^']+)'/", $html, $matches)) {
        $episode = $matches[1];
    }
    
    if (preg_match("/var nonce = '([^']+)'/", $html, $matches)) {
        $nonce = $matches[1];
    }
    
    // Now make AJAX call with session cookies
    $ajaxUrl = 'https://9anime.org.lv/wp-admin/admin-ajax.php?action=fetch_download_links&mal_id=' . $malId . '&ep=' . $episode . '&nonce=' . $nonce;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ajaxUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: ' . $episodeUrl,
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = $response ? json_decode($response, true) : null;
    
    if (isset($data['data']['result'])) {
        $links = [];
        $htmlContent = $data['data']['result'];
        
        // Extract download links
        if (preg_match_all('/<a[^>]*href="([^"]+)"[^>]*class="btn btn-primary"[^>]*>([^<]+)<\/a>/i', $htmlContent, $matches, PREG_SET_ORDER)) {
            $currentType = 'Sub'; // Default
            
            // Check for Sub/Dub sections
            if (preg_match('/Sub<\/div>.*?Dub<\/div>/s', $htmlContent)) {
                $subSection = preg_match('/Sub<\/div>(.*?)Dub<\/div>/s', $htmlContent, $subMatch) ? $subMatch[1] : '';
                $dubSection = preg_match('/Dub<\/div>(.*?)$/s', $htmlContent, $dubMatch) ? $dubMatch[1] : '';
                
                // Extract Sub links
                if (preg_match_all('/<a[^>]*href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $subSection, $subMatches, PREG_SET_ORDER)) {
                    foreach ($subMatches as $match) {
                        $links[] = [
                            'url' => $match[1],
                            'quality' => trim($match[2]),
                            'type' => 'Sub',
                            'server' => 'kwik'
                        ];
                    }
                }
                
                // Extract Dub links
                if (preg_match_all('/<a[^>]*href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $dubSection, $dubMatches, PREG_SET_ORDER)) {
                    foreach ($dubMatches as $match) {
                        $links[] = [
                            'url' => $match[1],
                            'quality' => trim($match[2]),
                            'type' => 'Dub',
                            'server' => 'kwik'
                        ];
                    }
                }
            } else {
                // Single type (Sub only)
                foreach ($matches as $match) {
                    $links[] = [
                        'url' => $match[1],
                        'quality' => trim($match[2]),
                        'type' => 'Sub',
                        'server' => 'kwik'
                    ];
                }
            }
        }
        
        return [
            'mal_id' => $malId,
            'episode' => $episode,
            'links' => $links,
            'api' => 'apiv3.php'
        ];
    }
    
    $result = [
        'mal_id' => $malId,
        'episode' => $episode,
        'nonce' => $nonce,
        'http_code' => $httpCode,
        'response' => $data,
        'api' => 'apiv3.php'
    ];
    
    // Cache the result if successful
    if (isset($links) && !empty($links)) {
        setCache($cache_key, json_encode([
            'mal_id' => $malId,
            'episode' => $episode,
            'links' => $links,
            'api' => 'apiv3.php'
        ]));
    }
    
    return $result;
}

function scrapeAnimeDetails($url, $id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) return ['error' => 'Failed to fetch page'];
    
    $details = [];
    
    // Add ID from URL
    $details['id'] = $id;
    
    // Extract title
    if (preg_match('/<h1[^>]*class="entry-title"[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
        $details['title'] = html_entity_decode(trim($matches[1]));
    } elseif (preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
        $title = html_entity_decode(trim($matches[1]));
        $details['title'] = str_replace(' - 9anime', '', $title);
    } else {
        $details['title'] = 'Unknown';
    }
    
    // Extract thumbnail
    if (preg_match('/<img[^>]*class="[^"]*ts-post-image[^"]*wp-post-image[^"]*"[^>]*src="([^"]+)"/i', $html, $matches)) {
        $details['thumbnail'] = $matches[1];
    } elseif (preg_match('/<img[^>]*src="([^"]+)"[^>]*class="[^"]*wp-post-image[^"]*"/i', $html, $matches)) {
        $details['thumbnail'] = $matches[1];
    } else {
        $details['thumbnail'] = '';
    }
    
    // Extract description
    if (preg_match('/<div[^>]*class="[^"]*entry-content[^"]*"[^>]*>(.*?)<div/s', $html, $matches)) {
        $desc = strip_tags($matches[1]);
        $details['description'] = trim(preg_replace('/\s+/', ' ', $desc));
    } else {
        $details['description'] = 'Description not available';
    }
    
    // Extract status
    if (preg_match('/<b>Status:<\/b>\s*([^<]+)</i', $html, $matches)) {
        $details['status'] = trim($matches[1]);
    } elseif (preg_match('/<div[^>]*class="status[^"]*">([^<]+)<\/div>/i', $html, $matches)) {
        $details['status'] = trim($matches[1]);
    } else {
        $details['status'] = 'Unknown';
    }
    
    // Extract genres
    $genres = [];
    if (preg_match('/<div[^>]*class="genxed"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
        if (preg_match_all('/<a[^>]*href="[^"]*\/genres\/[^"]*"[^>]*>([^<]+)<\/a>/i', $matches[1], $genreMatches)) {
            $genres = array_map('trim', $genreMatches[1]);
        }
    }
    $details['genre'] = $genres;
    $details['api'] = 'apiv3.php';
    
    return $details;
}

function scrapeAnime($url) {
    // Check cache first
    $cache_key = getCacheKey($url);
    $cached = getCache($cache_key);
    if ($cached !== false) {
        return json_decode($cached, true);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $httpCode !== 200) return [];
    
    $animeList = [];
    
    // Extract anime items using regex
    preg_match_all('/<article[^>]*class="bs"[^>]*>(.*?)<\/article>/s', $html, $articles);
    
    foreach ($articles[1] as $article) {
        $anime = [];
        
        // Extract ID from href (handle both anime and episode URLs)
        if (preg_match('/href="[^"]*\/anime\/([^\/]+)\/?"/i', $article, $matches)) {
            $anime['id'] = $matches[1];
        } elseif (preg_match('/href="[^"]*\/([^\/]+)-episode-\d+\/?"/i', $article, $matches)) {
            $anime['id'] = $matches[1];
        } elseif (preg_match('/href="[^"]*\/([^\/]+)\/?"/i', $article, $matches)) {
            $anime['id'] = $matches[1];
        }
        
        // Extract title from title attribute or alt text
        if (preg_match('/title="([^"]+)"/i', $article, $matches)) {
            $anime['title'] = html_entity_decode($matches[1]);
        } elseif (preg_match('/alt="([^"]+)"/i', $article, $matches)) {
            $anime['title'] = html_entity_decode($matches[1]);
        }
        
        // Extract thumbnail
        if (preg_match('/src="([^"]+)"/i', $article, $matches)) {
            $anime['thumbnail'] = $matches[1];
        }
        
        if (!empty($anime['title'])) {
            $anime['api'] = 'apiv3.php';
            $animeList[] = $anime;
        }
    }
    
    // Cache the result
    setCache($cache_key, json_encode($animeList));
    
    return $animeList;
}

// Get action parameter
$action = $_GET['action'] ?? '';
$genre = $_GET['genre'] ?? '';
$id = $_GET['id'] ?? '';

$baseUrl = 'https://9anime.org.lv';
$urls = [
    'home' => $baseUrl . '/',
    'popular' => $baseUrl . '/most-popular/',
    'action-adventure' => $baseUrl . '/latest-action-adventure/',
    'anime-list' => $baseUrl . '/anime/',
    'series-ova' => $baseUrl . '/latest-series-ova/',
    'action-movie' => $baseUrl . '/latest-action-movie/',
    'latest' => $baseUrl . '/',
    'trending' => $baseUrl . '/most-popular/',
    'completed' => $baseUrl . '/anime/?status=completed',
    'ongoing' => $baseUrl . '/anime/?status=ongoing'
];

// Handle getep action - optimized episodes with all links
if ($action === 'getep') {
    if (empty($id)) {
        echo json_encode(['error' => 'ID parameter required. Example: ?action=getep&id=frieren-beyond-journeys-end']);
        exit;
    }
    
    $animeUrl = $baseUrl . '/anime/' . $id . '/';
    $episodesData = scrapeAllEpisodes($animeUrl, $baseUrl);
    
    echo json_encode($episodesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle episodes action
if ($action === 'episodes') {
    if (empty($id)) {
        echo json_encode(['error' => 'ID parameter required. Example: ?action=episodes&id=frieren-beyond-journeys-end']);
        exit;
    }
    
    $animeUrl = $baseUrl . '/anime/' . $id . '/';
    $episodesData = scrapeEpisodes($animeUrl, $baseUrl);
    
    echo json_encode($episodesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle download action - single episode by ID
if ($action === 'download') {
    // Check if it's ep parameter (new format)
    $ep = isset($_GET['ep']) ? $_GET['ep'] : null;
    
    if ($ep) {
        // New format: download&ep=frieren-beyond-journeys-end-episode-11
        $episodeUrl = $baseUrl . '/' . $ep . '/';
        $downloadData = scrapeDownloadLinks($episodeUrl);
    } elseif (!empty($id)) {
        // Old format: download&id=frieren-beyond-journeys-end-episode-11
        $episodeUrl = $baseUrl . '/' . $id . '/';
        $downloadData = scrapeDownloadLinks($episodeUrl);
    } else {
        echo json_encode(['error' => 'Episode ID required. Example: ?action=download&ep=frieren-beyond-journeys-end-episode-11']);
        exit;
    }
    
    echo json_encode($downloadData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle details action
if ($action === 'details') {
    if (empty($id)) {
        echo json_encode(['error' => 'ID parameter required. Example: ?action=details&id=anime-slug']);
        exit;
    }
    
    $detailsUrl = $baseUrl . '/anime/' . $id . '/';
    $details = scrapeAnimeDetails($detailsUrl, $id);
    
    echo json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle genre action
if ($action === 'genre') {
    if (empty($genre)) {
        echo json_encode(['error' => 'Genre parameter required. Example: ?action=genre&genre=action']);
        exit;
    }
    
    // Handle multiple genres: split by + 
    $genres = explode('+', strtolower($genre));
    $genreParams = array();
    foreach ($genres as $g) {
        $genreParams[] = 'genre%5B%5D=' . trim($g);
    }
    
    $targetUrl = $baseUrl . '/anime/?' . implode('&', $genreParams) . '&status=&type=&sub=&order=';
    
    // Debug output
    if (isset($_GET['debug'])) {
        echo json_encode(['url' => $targetUrl, 'genres' => $genres]);
        exit;
    }
    
} else {
    if (!isset($urls[$action])) {
        echo json_encode(['error' => 'Invalid action. Available: ' . implode(', ', array_keys($urls)) . ', genre, details, episodes, getep, download']);
        exit;
    }
    $targetUrl = $urls[$action];
}

$animes = scrapeAnime($targetUrl);
echo json_encode($animes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
