<?php
/**
 * Statistics API Endpoint
 * GET /api/stats.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check authentication
require_once 'auth_check.php';
checkAuth();

require_once '../config/config.php';

try {
    $supabaseUrl = SUPABASE_URL . '/rest/v1';
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];
    
    // Get total users
    $usersResponse = makeRequest('GET', $supabaseUrl . '/users?select=count', $headers);
    $totalUsers = $usersResponse[0]['count'] ?? 0;
    
    // Get active FCM tokens
    $tokensResponse = makeRequest('GET', $supabaseUrl . '/fcm_tokens?is_active=eq.true&select=count', $headers);
    $activeTokens = $tokensResponse[0]['count'] ?? 0;
    
    // Get notifications sent today
    $today = date('Y-m-d');
    $todayResponse = makeRequest('GET', $supabaseUrl . '/notifications?sent_at=gte.' . $today . 'T00:00:00&select=count', $headers);
    $sentToday = $todayResponse[0]['count'] ?? 0;
    
    // Get total notifications sent
    $totalResponse = makeRequest('GET', $supabaseUrl . '/notifications?select=count', $headers);
    $totalSent = $totalResponse[0]['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => $totalUsers,
            'active_tokens' => $activeTokens,
            'sent_today' => $sentToday,
            'total_sent' => $totalSent
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function makeRequest($method, $url, $headers, $data = null) {
    $context = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ];
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $context['http']['content'] = json_encode($data);
    }
    
    $response = file_get_contents($url, false, stream_context_create($context));
    
    if ($response === false) {
        throw new Exception("Request failed: $method $url");
    }
    
    return json_decode($response, true);
}
?>
