<?php
/**
 * Dashboard Send Notification API (Session Auth)
 * POST /notification/api/dashboard_send.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication (session-based for dashboard)
require_once 'auth_check.php';
checkAuth();

require_once '../config/config.php';
require_once '../classes/NotificationSender.php';

try {
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($data['title']) || !isset($data['body']) || !isset($data['send_type'])) {
        throw new Exception('Missing required fields: title, body, send_type');
    }
    
    // Initialize notification sender
    $sender = new NotificationSender();
    
    // Send notification based on type
    if ($data['send_type'] === 'all') {
        // Send to all users
        $result = $sender->sendToAllUsers([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => [
                'source' => 'notification_center',
                'timestamp' => date('c'),
                'notification_type' => $data['type'] ?? 'general'
            ]
        ]);
    } elseif ($data['send_type'] === 'specific') {
        // Send to specific user
        if (!isset($data['user_id'])) {
            throw new Exception('user_id required for specific notifications');
        }
        
        $result = $sender->sendToUser($data['user_id'], [
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => [
                'source' => 'notification_center',
                'timestamp' => date('c'),
                'notification_type' => $data['type'] ?? 'general'
            ]
        ]);
    } else {
        throw new Exception('Invalid send_type. Must be "all" or "specific"');
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
        // Log detailed error
        error_log("Notification send failed: " . json_encode($result));
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error',
            'debug' => $result // Add full result for debugging
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
