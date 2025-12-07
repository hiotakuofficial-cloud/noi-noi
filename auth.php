<?php
function verifyApiToken() {
    $validToken = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
    
    // Get token from various sources
    $token = $_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    // Remove Bearer prefix if present
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing token fuck you scraper']);
        exit;
    }
    
    if (!hash_equals($validToken, $token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Giveing Wrong Token You Stupid Bastard !']);
        exit;
    }
    
    return true;
}
?>
