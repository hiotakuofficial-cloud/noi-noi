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
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $priority = $input['priority'] ?? 'medium';
    
    if (empty($title) || empty($description)) {
        throw new Exception('Title and description are required');
    }
    
    // Calculate end date based on priority
    $startDate = date('Y-m-d H:i:s');
    $daysMap = ['low' => 7, 'medium' => 14, 'high' => 30, 'critical' => 90];
    $days = $daysMap[$priority] ?? 14;
    $endDate = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    // Insert announcement
    $data = [
        'title' => $title,
        'description' => $description,
        'priority' => $priority,
        'is_active' => true,
        'start_date' => $startDate,
        'end_date' => $endDate
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
    
    $response = file_get_contents(SUPABASE_URL . '/rest/v1/announcements', false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to create announcement');
    }
    
    $result = json_decode($response, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully',
        'announcement' => $result[0] ?? null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
