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

// Search functions using included files
function searchMainAPI($query) {
    try {
        // Load environment and set token
        loadEnv(__DIR__ . '/../../../../../.env');
        $token = $_ENV['BABEER'] ?? '';
        
        // Backup original GET
        $originalGet = $_GET;
        
        // Set search parameters
        $_GET = [
            'action' => 'search',
            'q' => $query,
            'token' => $token
        ];
        
        ob_start();
        include __DIR__ . '/../../../../../../api.php';
        $response = ob_get_clean();
        
        // Restore original GET
        $_GET = $originalGet;
        
        $data = json_decode($response, true);
        if ($data && isset($data['results'])) {
            return $data['results'];
        }
        if ($data && isset($data['data'])) {
            return $data['data'];
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

function searchHindiAPI($query) {
    try {
        // Load environment and set token
        loadEnv(__DIR__ . '/../../../../../.env');
        $token = $_ENV['BABEER'] ?? '';
        
        // Backup original GET
        $originalGet = $_GET;
        
        // Set search parameters
        $_GET = [
            'action' => 'search',
            'q' => $query,
            'token' => $token
        ];
        
        ob_start();
        include __DIR__ . '/../../../../../../hindiv2.php';
        $response = ob_get_clean();
        
        // Restore original GET
        $_GET = $originalGet;
        
        $data = json_decode($response, true);
        
        // Handle different response formats
        if (is_array($data)) {
            // Hindi API returns direct array
            return $data;
        }
        if ($data && isset($data['results'])) {
            return $data['results'];
        }
        if ($data && isset($data['data'])) {
            return $data['data'];
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
        $results = searchMainAPI($query);
        break;
        
    case 'hindi':
        $results = searchHindiAPI($query);
        break;
        
    case 'all':
    default:
        $mainResults = searchMainAPI($query);
        $hindiResults = searchHindiAPI($query);
        
        // Combine results with source tags
        foreach ($mainResults as $item) {
            $item['source'] = 'main';
            $results[] = $item;
        }
        
        foreach ($hindiResults as $item) {
            $item['source'] = 'hindi';
            $results[] = $item;
        }
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
