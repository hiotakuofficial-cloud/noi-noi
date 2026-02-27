<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/NotificationSender.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['title']) || !isset($data['body'])) {
        throw new Exception('Missing required fields');
    }
    
    $sendType = $data['send_type'] ?? 'all';
    
    $notificationData = [
        'source' => 'admin_dashboard',
        'timestamp' => date('c'),
        'notification_type' => $data['type'] ?? 'general',
        'click_action' => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => $data['screen'] ?? '/main'
    ];
    
    $sender = new NotificationSender();
    
    if ($sendType === 'all') {
        $result = $sender->sendToAllUsers([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => $notificationData
        ]);
    } else {
        if (!isset($data['user_id'])) {
            throw new Exception('user_id required');
        }
        
        $result = $sender->sendToUser($data['user_id'], [
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'data' => $notificationData
        ]);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
