<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/supabase.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

if (empty($endpoint)) {
    echo json_encode(['error' => 'Endpoint required']);
    exit;
}

// Build Supabase URL
$url = SUPABASE_URL . '/rest/v1/' . $endpoint;

// Get query params
$query = $_GET;
unset($query['endpoint']);
if (!empty($query)) {
    $url .= '?' . http_build_query($query);
}

// Prepare headers
$headers = [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json'
];

// Add Prefer header for PATCH/POST
if (in_array($method, ['PATCH', 'POST', 'DELETE'])) {
    $headers[] = 'Prefer: return=minimal';
}

// Get request body
$body = file_get_contents('php://input');

// Make request
$context = stream_context_create([
    'http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $body,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($url, false, $context);

// Return response
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Request failed']);
} else {
    // Get status code from headers
    if (isset($http_response_header[0])) {
        preg_match('/\d{3}/', $http_response_header[0], $matches);
        if (!empty($matches)) {
            http_response_code((int)$matches[0]);
        }
    }
    echo $response;
}
