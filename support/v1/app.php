<?php
/**
 * Hiotaku App Download API v2.0
 * Simple app download link management
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Rate limiting
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitFile = '/tmp/app_rate_' . md5($clientIP);
    
    if (file_exists($rateLimitFile)) {
        $lastRequest = (int)file_get_contents($rateLimitFile);
        if (time() - $lastRequest < 1) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
            exit;
        }
    }
    file_put_contents($rateLimitFile, time());
    
    // Input validation
    $allowedFields = ['authkey', 'authkey2', 'url', 'action'];
    $extraFields = array_diff(array_keys($_POST + $_GET), $allowedFields);
    
    if (!empty($extraFields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid fields detected',
            'invalid_fields' => array_values($extraFields)
        ]);
        exit;
    }
    
    // Authentication
    $authKey1 = filter_input(INPUT_POST, 'authkey', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 
                filter_input(INPUT_GET, 'authkey', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $authKey2 = filter_input(INPUT_POST, 'authkey2', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 
                filter_input(INPUT_GET, 'authkey2', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if ($authKey1 !== 'nehubaby' || $authKey2 !== 'pihupapa') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication failed']);
        exit;
    }
    
    // Supabase configuration
    $supabaseUrl = 'https://brwzqawoncblbxqoqyua.supabase.co/rest/v1/app_downloads';
    $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMzM1MjIsImV4cCI6MjA3NzkwOTUyMn0.-HNrfcz5K2N6f_Q8tQsWtsUJCV_SW13Hcj565qU5eCA';
    
    function dbRequest($endpoint, $data = null, $method = 'GET') {
        global $supabaseKey;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $supabaseKey,
                'Authorization: Bearer ' . $supabaseKey,
                'Prefer: return=representation'
            ]
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('Database error: HTTP ' . $httpCode . ' Response: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    switch ($action) {
        case 'getapplink':
            // Get active app download link
            $endpoint = $supabaseUrl . '?is_active=eq.true&order=created_at.desc&limit=1';
            $apps = dbRequest($endpoint);
            
            if (empty($apps)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No app download link available'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'download_link' => $apps[0]['app_url']
                ]);
            }
            break;
            
        case 'updateurl':
            $newUrl = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL) ?: 
                      filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
            
            if (empty($newUrl) || !filter_var($newUrl, FILTER_VALIDATE_URL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Valid URL required'
                ]);
                exit;
            }
            
            // Deactivate all existing URLs
            $deactivateEndpoint = $supabaseUrl . '?is_active=eq.true';
            $deactivateData = ['is_active' => false];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $deactivateEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => json_encode($deactivateData),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'apikey: ' . $supabaseKey,
                    'Authorization: Bearer ' . $supabaseKey
                ]
            ]);
            curl_exec($ch);
            curl_close($ch);
            
            // Add new URL
            $newData = ['app_url' => $newUrl];
            $result = dbRequest($supabaseUrl, $newData, 'POST');
            
            echo json_encode([
                'success' => true,
                'message' => 'App download URL updated successfully',
                'new_url' => $newUrl
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Invalid action',
                'valid_actions' => ['getapplink', 'updateurl']
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
    
    error_log('App API Error: ' . $e->getMessage());
}
?>
