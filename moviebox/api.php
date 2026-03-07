<?php
/**
 * MovieBox Web API Client (No Auth Required)
 */

require_once __DIR__ . '/../auth.php';

// Verify API token
verifyApiToken();

// Simple cache system (same as api.php)
$cache_dir = '/tmp/moviebox_cache/';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0755, true);
}

function mb_getCacheKey($url) {
    return md5($url);
}

function mb_getCache($key) {
    global $cache_dir;
    $file = $cache_dir . $key;
    if (file_exists($file) && (time() - filemtime($file)) < 3600) { // 1h cache
        return file_get_contents($file);
    }
    return false;
}

function mb_setCache($key, $data, $ttl = 3600) {
    global $cache_dir;
    @file_put_contents($cache_dir . $key, $data);
}

class MovieBoxAPI {
    private $baseUrl = 'https://themoviebox.org/wefeed-h5api-bff';
    private $token = null;
    private $cookies = [];
    
    public function setCookies($cookies) {
        $this->cookies = $cookies;
    }
    
    private function getToken() {
        if ($this->token) {
            return $this->token;
        }
        
        // Get token from trending endpoint
        $ch = curl_init($this->baseUrl . '/subject/trending?page=0&perPage=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'X-Client-Info: {"timezone":"Asia/Calcutta"}'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Extract x-user header
        if (preg_match('/x-user: ({.*?})/i', $response, $matches)) {
            $userData = json_decode($matches[1], true);
            $this->token = $userData['token'] ?? null;
        }
        
        return $this->token;
    }
    
    private function request($endpoint, $params = [], $useAuth = false, $refererPath = null) {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Check cache first (except for play endpoint)
        if ($endpoint !== '/subject/play') {
            $cacheKey = mb_getCacheKey($url);
            $cached = mb_getCache($cacheKey);
            if ($cached !== false) {
                return json_decode($cached, true);
            }
        }
        
        $referer = $refererPath ? "https://themoviebox.org{$refererPath}" : 'https://themoviebox.org/';
        
        $headers = [
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9',
            'X-Client-Info: {"timezone":"Asia/Calcutta"}',
            'X-Source: ',
            'cdn: Transsion',
            'Origin: https://themoviebox.org',
            "Referer: {$referer}",
            'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
            'sec-ch-ua: "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile: ?1',
            'sec-ch-ua-platform: "Android"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin'
        ];
        
        if ($useAuth) {
            $token = $this->getToken();
            if ($token) {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
        }
        
        if (!empty($this->cookies)) {
            $cookieStr = [];
            foreach ($this->cookies as $name => $value) {
                $cookieStr[] = "$name=$value";
            }
            $headers[] = 'Cookie: ' . implode('; ', $cookieStr);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => 'HTTP ' . $httpCode];
        }
        
        $result = json_decode($response, true);
        
        // Cache successful responses (except play endpoint)
        if ($endpoint !== '/subject/play' && isset($result['code']) && $result['code'] === 0) {
            $ttl = $this->getCacheTTL($endpoint);
            mb_setCache($cacheKey, $response, $ttl);
        }
        
        return $result;
    }
    
    private function getCacheTTL($endpoint) {
        $ttls = [
            '/home' => 3600,              // 1 hour
            '/subject/trending' => 1800,  // 30 min
            '/subject/search' => 1800,    // 30 min
            '/detail' => 7200,            // 2 hours
            '/subject/detail-rec' => 3600,// 1 hour
            '/subject/caption' => 86400   // 24 hours
        ];
        return $ttls[$endpoint] ?? 3600;
    }
    
    public function getHome() {
        return $this->request('/home');
    }
    
    public function getDetail($subjectId) {
        return $this->request('/detail', ['subjectId' => $subjectId]);
    }
    
    public function getDetailByPath($detailPath) {
        return $this->request('/detail', ['detailPath' => $detailPath]);
    }
    
    public function getTrending($page = 0, $perPage = 18) {
        return $this->request('/subject/trending', ['page' => $page, 'perPage' => $perPage]);
    }
    
    public function search($keyword, $page = 0, $perPage = 28, $subjectType = 0) {
        $endpoint = '/subject/search';
        $url = $this->baseUrl . $endpoint;
        
        // Check cache first
        $cacheKey = mb_getCacheKey($url . '?' . http_build_query(compact('keyword', 'page', 'perPage', 'subjectType')));
        $cached = mb_getCache($cacheKey);
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        
        $token = $this->getToken();
        
        $postData = json_encode([
            'keyword' => $keyword,
            'page' => (string)$page,
            'perPage' => $perPage,
            'subjectType' => $subjectType
        ]);
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Client-Info: {"timezone":"Asia/Calcutta"}',
            'Authorization: Bearer ' . $token,
            'X-Request-Lang: en',
            'Origin: https://themoviebox.org',
            'Referer: https://themoviebox.org/',
            'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => 'HTTP ' . $httpCode];
        }
        
        $result = json_decode($response, true);
        
        // Cache successful responses
        if (isset($result['code']) && $result['code'] === 0) {
            mb_setCache($cacheKey, $response, 1800); // 30 min
        }
        
        return $result;
    }
    
    public function getRecommendations($subjectId, $page = 1, $perPage = 12) {
        return $this->request('/subject/detail-rec', [
            'subjectId' => $subjectId,
            'page' => $page,
            'perPage' => $perPage
        ]);
    }
    
    public function getPlayUrls($subjectId, $detailPath, $season = 0, $episode = 0) {
        $refererPath = "/movies/{$detailPath}?id={$subjectId}&type=/movie/detail";
        return $this->request('/subject/play', [
            'subjectId' => $subjectId,
            'detailPath' => $detailPath,
            'se' => $season,
            'ep' => $episode
        ], false, $refererPath);
    }
    
    public function getCaptions($id, $subjectId, $detailPath, $format = 'MP4') {
        return $this->request('/subject/caption', [
            'format' => $format,
            'id' => $id,
            'subjectId' => $subjectId,
            'detailPath' => $detailPath
        ]);
    }
}

// Test
header('Content-Type: application/json');

$api = new MovieBoxAPI();

$action = $_GET['action'] ?? 'home';

switch ($action) {
    case 'home':
        echo json_encode($api->getHome(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'detail':
        $id = $_GET['id'] ?? '';
        $path = $_GET['path'] ?? '';
        
        if (!empty($path)) {
            echo json_encode($api->getDetailByPath($path), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif (!empty($id)) {
            echo json_encode($api->getDetail($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Missing id or path parameter'], JSON_PRETTY_PRINT);
        }
        break;
        
    case 'trending':
        $page = $_GET['page'] ?? 0;
        $perPage = $_GET['perPage'] ?? 18;
        echo json_encode($api->getTrending($page, $perPage), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
    
    case 'search':
        $keyword = $_GET['keyword'] ?? '';
        $page = $_GET['page'] ?? 0;
        $perPage = $_GET['perPage'] ?? 28;
        $subjectType = $_GET['subjectType'] ?? 0;
        
        if (empty($keyword)) {
            echo json_encode(['error' => 'Keyword is required'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode($api->search($keyword, $page, $perPage, $subjectType), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'recommendations':
        $id = $_GET['id'] ?? '';
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['perPage'] ?? 12;
        if (empty($id)) {
            echo json_encode(['error' => 'Missing id parameter'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode($api->getRecommendations($id, $page, $perPage), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'play':
        $id = $_GET['id'] ?? '';
        $path = $_GET['path'] ?? '';
        $season = $_GET['season'] ?? 0;
        $episode = $_GET['episode'] ?? 0;
        
        if (empty($id) || empty($path)) {
            echo json_encode(['error' => 'Missing id or path parameter'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode($api->getPlayUrls($id, $path, $season, $episode), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'captions':
        $id = $_GET['id'] ?? '';
        $subjectId = $_GET['subjectId'] ?? '';
        $path = $_GET['path'] ?? '';
        $format = $_GET['format'] ?? 'MP4';
        
        if (empty($id) || empty($subjectId) || empty($path)) {
            echo json_encode(['error' => 'Missing id, subjectId or path parameter'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode($api->getCaptions($id, $subjectId, $path, $format), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'cache':
        $subAction = $_GET['sub'] ?? 'stats';
        switch ($subAction) {
            case 'stats':
                $files = glob($cache_dir . '*');
                $total = count($files);
                $size = array_sum(array_map('filesize', $files));
                echo json_encode([
                    'total' => $total,
                    'size' => $size,
                    'size_mb' => round($size / 1024 / 1024, 2)
                ], JSON_PRETTY_PRINT);
                break;
            case 'clear':
                array_map('unlink', glob($cache_dir . '*'));
                echo json_encode(['success' => true, 'message' => 'Cache cleared'], JSON_PRETTY_PRINT);
                break;
            default:
                echo json_encode(['error' => 'Invalid cache action'], JSON_PRETTY_PRINT);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action'], JSON_PRETTY_PRINT);
}
