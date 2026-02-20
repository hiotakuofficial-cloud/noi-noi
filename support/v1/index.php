<?php
/**
 * Hiotaku Support API v1.0
 * Production-grade support ticket management system
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Rate limiting (simple implementation)
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitFile = '/tmp/rate_limit_' . md5($clientIP);
    
    if (file_exists($rateLimitFile)) {
        $lastRequest = (int)file_get_contents($rateLimitFile);
        if (time() - $lastRequest < 1) { // 1 request per second
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
            exit;
        }
    }
    file_put_contents($rateLimitFile, time());
    
    // Input validation and sanitization
    $allowedFields = ['authkey', 'authkey2', 'username', 'userId', 'message', 'sender', 'supportId', 'support', 'action'];
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
    $supabaseUrl = 'https://brwzqawoncblbxqoqyua.supabase.co/rest/v1/support_messages';
    $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMzM1MjIsImV4cCI6MjA3NzkwOTUyMn0.-HNrfcz5K2N6f_Q8tQsWtsUJCV_SW13Hcj565qU5eCA';
    
    // Database request function
    function dbRequest($endpoint, $data = null, $method = 'GET') {
        global $supabaseKey;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Hiotaku-Support-API/1.0',
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
            throw new Exception('Database connection failed: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('Database error: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    switch ($action) {
        case 'support':
            // Input validation
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sender = filter_input(INPUT_POST, 'sender', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'user';
            
            // Validation rules
            if (empty($username) || empty($userId) || empty($message)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Required fields missing',
                    'required' => ['username', 'userId', 'message']
                ]);
                exit;
            }
            
            if (strlen($username) > 50 || strlen($userId) > 20 || strlen($message) > 1000) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Field length exceeded',
                    'limits' => ['username' => 50, 'userId' => 20, 'message' => 1000]
                ]);
                exit;
            }
            
            if (!in_array($sender, ['user', 'support', 'system'])) {
                $sender = 'user';
            }
            
            // Insert data
            $data = [
                'username' => $username,
                'user_id' => $userId,
                'sender' => $sender,
                'message' => $message
            ];
            
            $result = dbRequest($supabaseUrl, $data, 'POST');
            
            if ($result && isset($result[0]['id'])) {
                $supportTicket = 'HT' . str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Support request submitted successfully',
                    'support_ticket' => $supportTicket,
                    'ticket_id' => $result[0]['id'],
                    'timestamp' => $result[0]['created_at']
                ]);
            } else {
                throw new Exception('Failed to create support ticket');
            }
            break;
            
        case 'get':
            $support = filter_input(INPUT_GET, 'support', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;
            $limit = min($limit, 100); // Max 100 records
            
            if (empty($support)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Support parameter required',
                    'valid_values' => ['all', 'user_id']
                ]);
                exit;
            }
            
            if ($support === 'all') {
                $endpoint = $supabaseUrl . '?order=created_at.desc&limit=' . $limit;
            } else {
                $endpoint = $supabaseUrl . '?user_id=eq.' . urlencode($support) . '&order=created_at.desc&limit=' . $limit;
            }
            
            $messages = dbRequest($endpoint);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'count' => count($messages),
                'limit' => $limit
            ]);
            break;
            
        case 'delete':
            $supportId = filter_input(INPUT_POST, 'supportId', FILTER_VALIDATE_INT) ?: 
                        filter_input(INPUT_GET, 'supportId', FILTER_VALIDATE_INT);
            
            if (!$supportId || $supportId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Valid numeric support ID required'
                ]);
                exit;
            }
            
            $endpoint = $supabaseUrl . '?id=eq.' . $supportId;
            dbRequest($endpoint, null, 'DELETE');
            
            echo json_encode([
                'success' => true,
                'message' => 'Support ticket deleted successfully',
                'deleted_id' => $supportId
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Invalid action',
                'valid_actions' => ['support', 'get', 'delete']
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'Please try again later'
    ]);
    
    // Log error (in production, use proper logging)
    error_log('Support API Error: ' . $e->getMessage());
}
?>
