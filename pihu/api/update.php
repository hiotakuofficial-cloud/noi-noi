<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../pihu/config/supabase.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $version = $input['version'] ?? '';
    $updateName = $input['update_name'] ?? '';
    $link = $input['link'] ?? '';
    $description = $input['description'] ?? '';
    $updateType = $input['update_type'] ?? 'optional';
    
    if (empty($version) || empty($link) || empty($description)) {
        throw new Exception('Version, link and description are required');
    }
    
    // Delete old updates (keep only latest)
    $deleteContext = stream_context_create([
        'http' => [
            'method' => 'DELETE',
            'header' => "apikey: " . SUPABASE_ANON_KEY . "\r\n" .
                       "Authorization: Bearer " . SUPABASE_ANON_KEY . "\r\n"
        ]
    ]);
    
    file_get_contents(SUPABASE_URL . '/rest/v1/updates?version=neq.' . urlencode($version), false, $deleteContext);
    
    // Insert new update
    $data = [
        'version' => $version,
        'update_name' => $updateName ?: "Version $version",
        'link' => $link,
        'description' => $description
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "apikey: " . SUPABASE_ANON_KEY . "\r\n" .
                       "Authorization: Bearer " . SUPABASE_ANON_KEY . "\r\n" .
                       "Content-Type: application/json\r\n" .
                       "Prefer: return=representation",
            'content' => json_encode($data)
        ]
    ]);
    
    $response = file_get_contents(SUPABASE_URL . '/rest/v1/updates', false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to push update');
    }
    
    $result = json_decode($response, true);
    
    // Send notification if forced update
    if ($updateType === 'forced') {
        require_once __DIR__ . '/../../notification/classes/NotificationSender.php';
        $sender = new NotificationSender();
        $sender->sendToAllUsers([
            'title' => "🚀 Update Required: $version",
            'body' => "New version available. Please update now!",
            'data' => [
                'type' => 'update',
                'screen' => '/update',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Update pushed successfully',
        'update' => $result[0] ?? null,
        'notification_sent' => $updateType === 'forced'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
