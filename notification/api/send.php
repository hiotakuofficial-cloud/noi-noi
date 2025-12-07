<?php
/**
 * Public Send Notification API for Flutter App
 * POST /notification/api/app_send.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Token Authentication (same as main API)
function verifyApiToken() {
    $validToken = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
    
    // Get token from various sources
    $token = $_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    // Remove Bearer prefix if present
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Missing API token']);
        exit;
    }
    
    if (!hash_equals($validToken, $token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API token']);
        exit;
    }
    
    return true;
}

// Verify token
verifyApiToken();

require_once '../config/config.php';
require_once '../classes/NotificationSender.php';

try {
    // Get request data (handle both JSON and form data)
    $data = [];
    
    if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }
    } else {
        // Handle form data
        $data = $_POST;
    }
    
    // Validate required fields
    if (!isset($data['title']) || !isset($data['body'])) {
        throw new Exception('Missing required fields: title, body');
    }
    
    // Set default send type if not provided
    $sendType = $data['send_type'] ?? 'specific';
    
    // Prepare notification data with click action support
    $notificationData = [
        'source' => 'flutter_app',
        'timestamp' => date('c'),
        'notification_type' => $data['type'] ?? 'general',
        'click_action' => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => $data['screen'] ?? '/home',
        'extra_data' => $data['data'] ?? []
    ];
    
    // Add specific data based on notification type
    if (isset($data['movie_id'])) {
        $notificationData['movie_id'] = $data['movie_id'];
    }
    if (isset($data['user_target_id'])) {
        $notificationData['user_target_id'] = $data['user_target_id'];
    }
    if (isset($data['action_data'])) {
        $notificationData['action_data'] = $data['action_data'];
    }
    
    // Initialize notification sender
    $sender = new NotificationSender();
    
    // Send notification based on type
    if ($sendType === 'all') {
        // Send to all users
        $result = $sender->sendToAllUsers([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => $notificationData
        ]);
    } else {
        // Send to specific user
        if (!isset($data['user_id'])) {
            throw new Exception('user_id required for specific notifications');
        }
        
        $result = $sender->sendToUser($data['user_id'], [
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => $notificationData
        ]);
    }
    
    // Return result
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully',
            'recipients_count' => $result['recipients_count'] ?? 1,
            'details' => $result['details'] ?? []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
