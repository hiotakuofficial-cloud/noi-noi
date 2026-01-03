<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Token Authentication
require_once '../auth.php';
verifyApiToken();

$cache_dir = '/tmp/hindi_download_cache_v2/';
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

$action = $_GET['action'] ?? '';

switch($action) {
    case 'home':
        $type = $_GET['type'] ?? 'hindi-dub';
        getHomeContent($type);
        break;
    case 'search':
        searchAnime();
        break;
    case 'get':
        $id = $_GET['id'] ?? '';
        $type = $_GET['type'] ?? 'hindi-dub';
        
        if (empty($id)) {
            echo json_encode(['error' => 'ID required'], JSON_PRETTY_PRINT);
            return;
        }
        
        switch($type) {
            case 'hindi-dub':
            case 'hindi-sub':
            case 'eng-sub':
            case 'jap-eng':
                getAnimeDownloadsByType($id, $type);
                break;
            default:
                echo json_encode(['error' => 'Invalid type. Use: hindi-dub, hindi-sub, eng-sub, jap-eng'], JSON_PRETTY_PRINT);
                break;
        }
        break;
    case 'anime':
        getAnimeDetails();
        break;
    case 'download':
        getDownloadLinks();
        break;
    default:
        echo json_encode(['error' => 'Invalid action. Use: home (with type=hindi-dub/hindi-sub/eng-sub/movie), search, get (with type=hindi-dub/hindi-sub/eng-sub/jap-eng), anime, download'], JSON_PRETTY_PRINT);
        break;
}

function makeRequest($url) {
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
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    if ($response !== false && !empty($response)) {
        setCache($cache_key, $response);
        return $response;
    }
    
    return false;
}

function getHomeContent($type) {
    try {
        $categoryMap = [
            'hindi-dub' => 9,    // Hindi Dub category
            'hindi-sub' => 9,    // Same category
            'eng-sub' => 9,      // Same category
            'movie' => 7         // Movie category
        ];
        
        $categoryId = $categoryMap[$type] ?? 4;
        $url = "https://animehindidub.com/wp-json/wp/v2/posts?categories={$categoryId}&per_page=50&orderby=date&order=desc";
        
        $response = makeRequest($url);
        $posts = json_decode($response, true);
        
        $results = [];
        foreach ($posts as $post) {
            // Get featured image
            $thumbnail = null;
            if (!empty($post['featured_media'])) {
                try {
                    $mediaUrl = "https://animehindidub.com/wp-json/wp/v2/media/" . $post['featured_media'];
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
                'thumbnail' => $thumbnail
            ];
        }
        
        echo json_encode([
            'success' => true,
            'type' => $type,
            'total' => count($results),
            'results' => $results
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get home content'], JSON_PRETTY_PRINT);
    }
}

function searchAnime() {
    $query = $_GET['q'] ?? '';
    if (empty($query)) {
        echo json_encode(['error' => 'Query required'], JSON_PRETTY_PRINT);
        return;
    }
    
    try {
        $url = "https://animehindidub.com/wp-json/wp/v2/posts?search=" . urlencode($query) . "&per_page=20";
        $response = makeRequest($url);
        $posts = json_decode($response, true);
        
        $results = [];
        foreach ($posts as $post) {
            // Get featured image
            $thumbnail = null;
            if (!empty($post['featured_media'])) {
                try {
                    $mediaUrl = "https://animehindidub.com/wp-json/wp/v2/media/" . $post['featured_media'];
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
                'slug' => $post['slug'],
                'thumbnail' => $thumbnail,
                'date' => $post['date']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'query' => $query,
            'total' => count($results),
            'results' => $results
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Search failed'], JSON_PRETTY_PRINT);
    }
}

function getAnimeDownloadsByType($id, $type) {
    try {
        $url = "https://animehindidub.com/wp-json/wp/v2/posts/" . $id;
        $response = makeRequest($url);
        $post = json_decode($response, true);
        
        $content = $post['content']['rendered'];
        
        // Extract anime info from content - multiple patterns
        $info = [];
        
        // Pattern 1: For series like Naruto
        if (preg_match('/<p><strong>Genre:\s*([^<]+)<\/strong>.*?<strong>Language:\s*([^<]+)<\/strong>.*?<strong>Quality:\s*([^<]+)<\/strong><\/p>/s', $content, $infoMatch)) {
            $info = [
                'Genre' => trim(strip_tags($infoMatch[1])),
                'Language' => trim(strip_tags($infoMatch[2])),
                'Quality' => trim(strip_tags($infoMatch[3]))
            ];
        }
        // Pattern 2: For movies like Dragon Ball
        elseif (preg_match('/<p>Ratings:\s*([^<]+)<br>Genre:\s*([^<]+)<br>.*?Language:\s*([^<]+)<br>.*?Quality:\s*([^<]+)<\/p>/s', $content, $infoMatch)) {
            $info = [
                'Rating' => trim(strip_tags($infoMatch[1])),
                'Genre' => trim(strip_tags($infoMatch[2])),
                'Language' => trim(strip_tags($infoMatch[3])),
                'Quality' => trim(strip_tags($infoMatch[4]))
            ];
        }
        
        $downloads = [];
        
        // Extract episode download links from wp-block-button elements
        preg_match_all('/<a class="wp-block-button__link[^"]*" href="([^"]+)"[^>]*>([^<]+)<\/a>/', $content, $buttonMatches, PREG_SET_ORDER);
        
        // Extract movie download links from paragraph text
        preg_match_all('/<strong>Download ([^–]+)–\s*<a href="([^"]+)"[^>]*>\[([^\]]+)\]<\/a>/s', $content, $movieMatches, PREG_SET_ORDER);
        
        // Process episode links
        foreach ($buttonMatches as $match) {
            $url = $match[1];
            $episodeText = trim($match[2]);
            
            // Determine quality based on section
            $quality = 'Unknown';
            $size = 'Unknown';
            
            // Check if it's in 1080p or 720p section
            $pos = strpos($content, $match[0]);
            $beforeText = substr($content, max(0, $pos - 500), 500);
            
            if (strpos($beforeText, '1080p Full HD') !== false) {
                $quality = '1080P';
                $size = '~500MB';
            } elseif (strpos($beforeText, '720p Full HD') !== false || strpos($beforeText, '720p HD') !== false) {
                $quality = '720P';
                $size = '~300MB';
            }
            
            $downloads[] = [
                'url' => $url,
                'episode' => $episodeText,
                'quality' => $quality,
                'platform' => 'GPLinks',
                'size' => $size,
                'type' => 'shortlink',
                'format' => 'episode'
            ];
        }
        
        // Process movie links
        foreach ($movieMatches as $match) {
            $qualitySize = trim($match[1]);
            $url = $match[2];
            $platform = trim($match[3]);
            
            // Extract quality and size
            $quality = 'Unknown';
            $size = 'Unknown';
            
            if (preg_match('/(\d+P)\s+.*?(\d+(?:\.\d+)?\s*[GM]B)/', $qualitySize, $qsMatch)) {
                $quality = $qsMatch[1];
                $size = $qsMatch[2];
            }
            
            $downloads[] = [
                'url' => $url,
                'quality' => $quality,
                'platform' => $platform,
                'size' => $size,
                'type' => 'shortlink',
                'format' => 'movie'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'id' => $post['id'],
            'title' => strip_tags($post['title']['rendered']),
            'info' => $info,
            'language_type' => $type,
            'total_downloads' => count($downloads),
            'downloads' => $downloads
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get downloads'], JSON_PRETTY_PRINT);
    }
}

function getAnimeDetails() {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['error' => 'ID required'], JSON_PRETTY_PRINT);
        return;
    }
    
    try {
        $url = "https://animehindidub.com/wp-json/wp/v2/posts/" . $id;
        $response = makeRequest($url);
        $post = json_decode($response, true);
        
        $content = $post['content']['rendered'];
        
        // Extract download links
        preg_match_all('/href="([^"]*)"/', $content, $matches);
        $downloadLinks = $matches[1] ?? [];
        
        // Extract anime info
        $info = [];
        if (preg_match('/<ul[^>]*>(.*?)<\/ul>/s', $content, $infoMatch)) {
            preg_match_all('/<li[^>]*><strong>([^:]+):\s*([^<]+)<\/strong><\/li>/', $infoMatch[1], $infoMatches);
            for ($i = 0; $i < count($infoMatches[1]); $i++) {
                $info[trim($infoMatches[1][$i])] = trim($infoMatches[2][$i]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'id' => $post['id'],
            'title' => strip_tags($post['title']['rendered']),
            'content' => strip_tags($post['content']['rendered']),
            'info' => $info,
            'download_links' => $downloadLinks,
            'featured_image' => $post['featured_media'] ?? null
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get anime details'], JSON_PRETTY_PRINT);
    }
}

function getDownloadLinks() {
    $url = $_GET['url'] ?? '';
    if (empty($url)) {
        echo json_encode(['error' => 'Download URL required'], JSON_PRETTY_PRINT);
        return;
    }
    
    try {
        $response = makeRequest($url);
        
        // Extract actual download links
        $links = [];
        
        // Mega.nz links
        preg_match_all('/href="(https:\/\/mega\.nz\/[^"]+)"/', $response, $megaMatches);
        foreach ($megaMatches[1] as $link) {
            $links[] = [
                'url' => $link,
                'type' => 'mega',
                'platform' => 'Mega.nz'
            ];
        }
        
        // Google Drive links
        preg_match_all('/href="(https:\/\/drive\.google\.com\/[^"]+)"/', $response, $driveMatches);
        foreach ($driveMatches[1] as $link) {
            $links[] = [
                'url' => $link,
                'type' => 'gdrive',
                'platform' => 'Google Drive'
            ];
        }
        
        // MediaFire links
        preg_match_all('/href="(https:\/\/www\.mediafire\.com\/[^"]+)"/', $response, $mediafireMatches);
        foreach ($mediafireMatches[1] as $link) {
            $links[] = [
                'url' => $link,
                'type' => 'mediafire',
                'platform' => 'MediaFire'
            ];
        }
        
        // Other file hosting services
        preg_match_all('/href="(https:\/\/[^"]*(?:hellabyte|dropbox|uploadhaven|hubcloud)[^"]*)"/', $response, $otherMatches);
        foreach ($otherMatches[1] as $link) {
            $platform = 'Unknown';
            if (strpos($link, 'hellabyte') !== false) $platform = 'HellaByte';
            if (strpos($link, 'dropbox') !== false) $platform = 'Dropbox';
            if (strpos($link, 'uploadhaven') !== false) $platform = 'UploadHaven';
            if (strpos($link, 'hubcloud') !== false) $platform = 'HubCloud';
            
            $links[] = [
                'url' => $link,
                'type' => 'other',
                'platform' => $platform
            ];
        }
        
        echo json_encode([
            'success' => true,
            'source_url' => $url,
            'total_links' => count($links),
            'links' => $links
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to extract download links'], JSON_PRETTY_PRINT);
    }
}
?>
