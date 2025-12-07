<?php
/**
 * Authentication check for API endpoints
 */

session_start();

function checkAuth() {
    // Check if user is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
    
    // Check session timeout (4 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
        session_destroy();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired'
        ]);
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}
?>
