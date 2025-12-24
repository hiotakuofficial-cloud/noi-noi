<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-UUID');

require_once __DIR__ . '/services/bot.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/supabase.php';

// Authenticate request
$auth = new Auth();
$auth->requireAuth();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    
    // Get user UUID from header (preferred) or fallback to body
    $userUuid = $_SERVER['HTTP_X_USER_UUID'] ?? $input['user_id'] ?? '';
    
    if(empty($message)) {
        echo json_encode(['error' => 'Message required']);
        exit;
    }
    
    if(empty($userUuid)) {
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    // Validate UUID/Firebase UID format
    if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userUuid) && !preg_match('/^[a-zA-Z0-9]{28}$/', $userUuid)) {
        echo json_encode(['error' => 'Invalid user ID format']);
        exit;
    }
    
    // Check if user exists and get user info
    $supabase = new SupabaseDB();
    if(!$supabase->userExists($userUuid)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Access denied',
            'message' => 'User not found in system. Please register first.'
        ]);
        exit;
    }
    
    // Get user info for personalization
    $userInfo = $supabase->getUserInfo($userUuid);
    $userName = $userInfo['display_name'] ?? $userInfo['username'] ?? 'User';
    
    // Initialize session
    $session = new ChatSession();
    
    // Get conversation context
    $context = $session->buildContext($userUuid);
    
    // Process message with context and user name
    $bot = new Bot();
    $response = $bot->processWithUserContext($message, $context, $userName);
    
    // Save to session history (Supabase + file backup)
    $session->addMessage($userUuid, $message, $response);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'user_id' => $userUuid,
        'user_name' => $userName,
        'timestamp' => time()
    ]);
} else {
    echo json_encode(['error' => 'POST method required']);
}
?>
