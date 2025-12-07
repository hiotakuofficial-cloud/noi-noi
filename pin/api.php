<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Token Authentication
require_once '../auth.php';
verifyApiToken();

if (!isset($_GET['action']) || !isset($_GET['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action or url parameter']);
    exit;
}

$action = $_GET['action'];
$url = $_GET['url'];

if ($action !== 'url') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Validate Pinterest URL
if (!preg_match('/pin\.it\/|pinterest\.com\/pin\//', $url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Pinterest URL']);
    exit;
}

try {
    // Step 1: Get page and CSRF token
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 10
        ]
    ]);
    
    $pageContent = file_get_contents('https://pinsave.onl/pingrab', false, $context);
    
    if (!$pageContent) {
        throw new Exception('Failed to connect to service');
    }
    
    // Extract CSRF token
    preg_match('/csrf-token" content="([^"]+)"/', $pageContent, $matches);
    if (!$matches) {
        throw new Exception('Service unavailable');
    }
    
    $csrfToken = $matches[1];
    
    // Extract cookies
    $cookies = '';
    foreach ($http_response_header as $header) {
        if (strpos($header, 'Set-Cookie:') === 0) {
            $cookie = explode(';', substr($header, 12))[0];
            $cookies .= $cookie . '; ';
        }
    }
    
    // Step 2: Submit download request
    $postData = http_build_query(['url' => $url, 'locale' => 'en']);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'X-CSRF-TOKEN: ' . $csrfToken,
                'Referer: https://pinsave.onl/pingrab',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Cookie: ' . rtrim($cookies, '; ')
            ],
            'content' => $postData,
            'timeout' => 15
        ]
    ]);
    
    $response = file_get_contents('https://pinsave.onl/downloader', false, $context);
    
    if (!$response) {
        throw new Exception('Processing failed');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !$data['status']) {
        throw new Exception('Media not found or unavailable');
    }
    
    // Parse download links
    $html = $data['html'];
    
    preg_match('/href="([^"]*download-file[^"]*)"/', $html, $videoMatch);
    preg_match('/href="([^"]*download-image[^"]*)"/', $html, $imageMatch);
    preg_match('/src="(https:\/\/i\.pinimg\.com[^"]*)"/', $html, $thumbMatch);
    
    $result = [
        'success' => true,
        'video_url' => isset($videoMatch[1]) ? $videoMatch[1] : null,
        'image_url' => isset($imageMatch[1]) ? $imageMatch[1] : null,
        'thumbnail' => isset($thumbMatch[1]) ? $thumbMatch[1] : null,
        'title' => 'Pinterest Media',
        'type' => (isset($videoMatch[1]) && isset($imageMatch[1])) ? 'both' : (isset($videoMatch[1]) ? 'video' : 'image')
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
