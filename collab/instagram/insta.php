<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include security layer
require_once 'security/layer.php';

// Get client IP
$clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Security checks
if (SecurityLayer::isIPBlocked($clientIP)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your IP is blocked due to suspicious activity']);
    exit;
}

if (!SecurityLayer::checkRateLimit($clientIP)) {
    SecurityLayer::logSecurityEvent('RATE_LIMIT_EXCEEDED', $clientIP);
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    exit;
}

if (!SecurityLayer::validateUserAgent()) {
    SecurityLayer::blockSuspiciousActivity($clientIP, 'Suspicious User Agent');
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
    return true;
}

// Load .env file
loadEnv('../.env');

// Get and validate input parameters
$action = SecurityLayer::sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');
$username = SecurityLayer::sanitizeInput($_GET['username'] ?? $_POST['username'] ?? '');
$barbeerKey = SecurityLayer::sanitizeInput($_GET['barbeer'] ?? $_POST['barbeer'] ?? '');
$key1 = SecurityLayer::sanitizeInput($_GET['key1'] ?? $_POST['key1'] ?? '');
$key2 = SecurityLayer::sanitizeInput($_GET['key2'] ?? $_POST['key2'] ?? '');

// Validate inputs
if (!SecurityLayer::validateInput($action, 'action')) {
    SecurityLayer::logSecurityEvent('INVALID_ACTION', $clientIP, $action);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action parameter']);
    exit;
}

if (!SecurityLayer::validateInput($username, 'username')) {
    SecurityLayer::logSecurityEvent('INVALID_USERNAME', $clientIP, $username);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid username format']);
    exit;
}

if (!SecurityLayer::validateInput($barbeerKey, 'key') || 
    !SecurityLayer::validateInput($key1, 'key') || 
    !SecurityLayer::validateInput($key2, 'key')) {
    SecurityLayer::logSecurityEvent('INVALID_KEYS', $clientIP);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid key format']);
    exit;
}

$validBarbeer = $_ENV['BARBEER_KEY'] ?? '';
$validKey1 = $_ENV['KEY_1'] ?? '';
$validKey2 = $_ENV['KEY_2'] ?? '';

if ($barbeerKey !== $validBarbeer || $key1 !== $validKey1 || $key2 !== $validKey2) {
    SecurityLayer::logSecurityEvent('INVALID_API_KEYS', $clientIP, 'Failed authentication attempt');
    SecurityLayer::blockSuspiciousActivity($clientIP, 'Invalid API keys');
    http_response_code(403);
    echo json_encode(['error' => 'Give Your Ass Ill Give Assescs To My Api']);
    exit;
}

// Log successful authentication
SecurityLayer::logSecurityEvent('SUCCESSFUL_AUTH', $clientIP, "Username: {$username}");

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$username = $_GET['username'] ?? $_POST['username'] ?? '';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$username = $_GET['username'] ?? $_POST['username'] ?? '';

if ($action !== 'insta') {
    echo json_encode(['error' => 'Invalid action. Use action=insta']);
    exit;
}

if (empty($username)) {
    echo json_encode(['error' => 'Username required']);
    exit;
}

function scrapeInstagramData($username) {
    $url = "https://socialstats.info/report/{$username}/instagram";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Referer: https://socialstats.info/'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$html) {
        return ['error' => 'Failed to fetch Instagram data'];
    }
    
    // Extract data using regex patterns
    $data = [
        'username' => $username,
        'name' => '',
        'bio' => '',
        'followers' => 0,
        'uploads' => 0,
        'engagement' => '0%',
        'status' => '',
        'profile_url' => "https://instagram.com/{$username}",
        'profile_image' => ''
    ];
    
    // Extract display name
    if (preg_match('/<h1>([^<]+)<\/h1>/', $html, $matches)) {
        $data['name'] = trim($matches[1]);
    }
    
    // Extract followers
    if (preg_match('/Followers\s*<p class="report-header-number">([^<]+)<\/p>/', $html, $matches)) {
        $followers = str_replace(',', '', trim($matches[1]));
        $data['followers'] = is_numeric($followers) ? (int)$followers : $followers;
    }
    
    // Extract uploads/posts
    if (preg_match('/Uploads\s*<p class="report-header-number">([^<]+)<\/p>/', $html, $matches)) {
        $uploads = str_replace(',', '', trim($matches[1]));
        $data['uploads'] = is_numeric($uploads) ? (int)$uploads : $uploads;
    }
    
    // Extract engagement
    if (preg_match('/Engagement\s*<p class="report-header-number">\s*([^<]+)\s*<\/p>/', $html, $matches)) {
        $data['engagement'] = trim($matches[1]);
    }
    
    // Extract bio/status (the text-muted content is actually the bio)
    if (preg_match('/<small class="text-muted">([^<]+)<\/small>/', $html, $matches)) {
        $data['bio'] = trim($matches[1]);
        $data['status'] = trim($matches[1]); // Keep both for compatibility
    }
    
    // Extract profile image
    if (preg_match('/img src="([^"]*ig-cdn\.link[^"]*)"/', $html, $matches)) {
        $data['profile_image'] = $matches[1];
    }
    
    return $data;
}

$result = scrapeInstagramData($username);

if (isset($result['error'])) {
    http_response_code(404);
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
}
?>
