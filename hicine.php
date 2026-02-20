<?php
require_once 'auth.php';

// Verify API token
verifyApiToken();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Base API URL
define('HICINE_API', 'https://api.hicine.info');

// Get request parameters
$action = $_GET['action'] ?? 'search';
$query = $_GET['q'] ?? '';
$id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
$type = preg_replace('/[^a-z_]/', '', $_GET['type'] ?? 'all');
$platform = preg_replace('/[^a-z_]/', '', $_GET['platform'] ?? '');
$limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);

// Function to fetch from Hicine API
function fetchHicine($endpoint) {
    $url = HICINE_API . $endpoint;
    
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $decoded;
}

// Route handler
switch ($action) {
    case 'search':
        if (empty($query)) {
            echo json_encode(['error' => 'Query parameter required'], JSON_PRETTY_PRINT);
            exit;
        }
        $data = fetchHicine("/api/search/" . urlencode($query));
        break;
        
    case 'recent':
        if ($type === 'all') {
            $data = fetchHicine("/api/recent");
        } else {
            $data = fetchHicine("/api/recent/{$type}");
        }
        break;
        
    case 'movie':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
            exit;
        }
        $data = fetchHicine("/api/movies/{$id}");
        break;
        
    case 'series':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
            exit;
        }
        // Try hollywood_series first, then bollywood_series, then anime
        $data = fetchHicine("/api/hollywood_series/{$id}");
        if ($data === null) {
            $data = fetchHicine("/api/bollywood_series/{$id}");
        }
        if ($data === null) {
            $data = fetchHicine("/api/anime/{$id}");
        }
        break;
        
    case 'anime':
        if (empty($id)) {
            echo json_encode(['error' => 'ID parameter required'], JSON_PRETTY_PRINT);
            exit;
        }
        $data = fetchHicine("/api/anime/{$id}");
        break;
        
    case 'bollywood_movies':
        if (empty($id)) {
            $data = fetchHicine("/api/bollywood_movies?offset={$offset}&limit={$limit}");
        } else {
            $data = fetchHicine("/api/bollywood_movies/{$id}");
        }
        break;
        
    case 'bollywood_series':
        if (empty($id)) {
            $data = fetchHicine("/api/bollywood_series?offset={$offset}&limit={$limit}");
        } else {
            $data = fetchHicine("/api/bollywood_series/{$id}");
        }
        break;
        
    case 'hollywood_movies':
        $data = fetchHicine("/api/hollywood_movies?offset={$offset}&limit={$limit}");
        break;
        
    case 'hollywood_series':
        $data = fetchHicine("/api/hollywood_series?offset={$offset}&limit={$limit}");
        break;
        
    case 'platform':
        if (empty($platform)) {
            echo json_encode(['error' => 'Platform parameter required'], JSON_PRETTY_PRINT);
            exit;
        }
        if ($type === 'all') {
            $data = fetchHicine("/api/platform/{$platform}");
        } else {
            $data = fetchHicine("/api/platform/{$platform}/{$type}");
        }
        break;
        
    case 'trending':
        $data = fetchHicine("/api/trending");
        break;
        
    case 'anime_list':
        $data = fetchHicine("/api/anime?offset={$offset}&limit={$limit}");
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action'], JSON_PRETTY_PRINT);
        exit;
}

// Return response
if ($data !== null) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data from Hicine API'], JSON_PRETTY_PRINT);
}
?>
