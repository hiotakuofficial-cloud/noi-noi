<?php
// Session check
if (!isset($_SESSION['admin_logged_in'])) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    header('Location: ../login/');
    exit;
}

// Banned check
require_once __DIR__ . '/../config/supabase.php';
$admin_id = $_SESSION['admin_id'];
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "apikey: " . SUPABASE_ANON_KEY . "\r\n" .
                   "Authorization: Bearer " . SUPABASE_ANON_KEY . "\r\n",
        'timeout' => 2
    ]
]);
$response = @file_get_contents(SUPABASE_URL . '/rest/v1/admins?id=eq.' . $admin_id . '&select=status', false, $context);
if ($response) {
    $admin = json_decode($response, true);
    if (!empty($admin) && $admin[0]['status'] !== 'active') {
        session_destroy();
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Account banned']);
            exit;
        }
        header('Location: ../banned.php');
        exit;
    }
}
