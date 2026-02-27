<?php
require_once __DIR__ . '/../config/supabase.php';

function loginAdmin($username, $password) {
    $url = SUPABASE_URL . '/rest/v1/admins?username=eq.' . urlencode($username) . '&select=*';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "apikey: " . SUPABASE_ANON_KEY . "\r\n" .
                       "Authorization: Bearer " . SUPABASE_ANON_KEY . "\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Connection error'];
    }
    
    $admins = json_decode($response, true);
    
    if (empty($admins)) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    $admin = $admins[0];
    
    if ($admin['status'] !== 'active') {
        return ['success' => false, 'error' => 'banned'];
    }
    
    if (!password_verify($password, $admin['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    return [
        'success' => true,
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'user_type' => $admin['user_type']
        ]
    ];
}
