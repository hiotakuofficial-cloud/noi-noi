<?php
/**
 * Users API Endpoint
 * GET /api/users.php
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
    
    // Get users with basic info
    $url = $supabaseUrl . '/users?select=id,username,email,display_name,is_active&is_active=eq.true&order=username.asc';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch users from Supabase');
    }
    
    $users = json_decode($response, true);
    
    if ($users === null) {
        throw new Exception('Invalid response from Supabase');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
