<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/check_auth.php';
require_once __DIR__ . '/../config/supabase.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password']) || !isset($data['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];
$userType = $data['user_type'];

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
    exit;
}

if (!in_array($userType, ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid user type']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$payload = json_encode([
    'user_type' => $userType,
    'username' => $username,
    'password_hash' => $passwordHash,
    'status' => 'active'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Prefer: return=minimal'
        ],
        'content' => $payload,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents(SUPABASE_URL . '/rest/v1/admins', false, $context);

if ($response === false || (isset($http_response_header[0]) && strpos($http_response_header[0], '201') === false && strpos($http_response_header[0], '200') === false)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create admin. Username may already exist.']);
    exit;
}

echo json_encode(['success' => true]);
