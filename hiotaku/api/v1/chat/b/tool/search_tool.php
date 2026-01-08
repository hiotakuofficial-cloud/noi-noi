<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, key');

// Load environment variables
function loadEnv($file) {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load environment
loadEnv(__DIR__ . '/../../../../../.env');

// Validate key
$key = $_SERVER['HTTP_KEY'] ?? '';
if ($key !== $_ENV['HISU_KEY']) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid key']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? $input['q'] ?? '';
$source = $input['source'] ?? 'all'; // all, main, hindi

if (empty($query)) {
    echo json_encode(['error' => 'Query required']);
    exit;
}

// Clean and prepare query for better search
$query = trim($query);
$query = str_replace(['-', '_'], ' ', $query); // Convert hyphens/underscores to spaces

// Validate query length (minimum 2 characters)
if (strlen($query) < 2) {
    echo json_encode(['error' => 'Query must be at least 2 characters']);
    exit;
}

// Search functions using Cloudflare Workers API
function searchAnimeAPI($query) {
    try {
        $url = 'https://anime-check-api.hiotaku-official.workers.dev/';
        $data = [
            'password' => 'nehubaby7890',
            'action' => 'check',
            'query' => $query
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $responseData = json_decode($response, true);
            $results = [];
            
            // Add Hindi results
            if (isset($responseData['hindi']['available']) && $responseData['hindi']['available']) {
                foreach ($responseData['hindi']['results'] as $item) {
                    $item['source'] = 'hindi';
                    $results[] = $item;
                }
            }
            
            // Add English results
            if (isset($responseData['english']['available']) && $responseData['english']['available']) {
                foreach ($responseData['english']['results'] as $item) {
                    $item['source'] = 'main';
                    $results[] = $item;
                }
            }
            
            return $results;
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

// Perform search based on source
$results = [];

switch ($source) {
    case 'main':
        $allResults = searchAnimeAPI($query);
        // Filter only main source results
        $results = array_filter($allResults, function($item) {
            return ($item['source'] ?? '') === 'main';
        });
        $results = array_values($results); // Re-index array
        break;
        
    case 'hindi':
        $allResults = searchAnimeAPI($query);
        // Filter only hindi source results
        $results = array_filter($allResults, function($item) {
            return ($item['source'] ?? '') === 'hindi';
        });
        $results = array_values($results); // Re-index array
        break;
        
    case 'all':
    default:
        $results = searchAnimeAPI($query); // This returns both sources
        break;
}

// Remove duplicates based on title
$uniqueResults = [];
$seenTitles = [];

foreach ($results as $item) {
    $title = strtolower($item['title'] ?? '');
    if (!in_array($title, $seenTitles)) {
        $seenTitles[] = $title;
        $uniqueResults[] = $item;
    }
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'source' => $source,
    'total' => count($uniqueResults),
    'data' => $uniqueResults
]);
?>
