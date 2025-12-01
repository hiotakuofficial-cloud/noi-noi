<?php
require_once '../../auth.php';

// Load environment variables
loadEnv('../.env');

// Verify token first
$tokenCheck = verifyToken();
if (!$tokenCheck['success']) {
    echo json_encode($tokenCheck);
    exit;
}

// Get parameters
$email = $_GET['gmail'] ?? '';
$otp = $_GET['otp'] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and OTP parameters required',
        'error_code' => 'MISSING_PARAMETERS'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format',
        'error_code' => 'INVALID_EMAIL'
    ]);
    exit;
}

// Validate OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid OTP format',
        'error_code' => 'INVALID_OTP_FORMAT'
    ]);
    exit;
}

// Get OTP from database
function getOTP($email) {
    $supabaseUrl = $_ENV['SUPAURL'];
    $supabaseKey = $_ENV['ANNOYKEY'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/otps?email=eq.' . urlencode($email));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    return !empty($data) ? $data[0] : null;
}

// Update OTP attempts
function updateOTPAttempts($email, $attempts) {
    $supabaseUrl = $_ENV['SUPAURL'];
    $supabaseKey = $_ENV['ANNOYKEY'];
    
    $data = ['attempts' => $attempts];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/otps?email=eq.' . urlencode($email));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_exec($ch);
    curl_close($ch);
}

// Mark OTP as used
function markOTPAsUsed($email) {
    $supabaseUrl = $_ENV['SUPAURL'];
    $supabaseKey = $_ENV['ANNOYKEY'];
    
    $data = ['used' => true];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/otps?email=eq.' . urlencode($email));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_exec($ch);
    curl_close($ch);
}

// Get OTP record
$otpRecord = getOTP($email);

if (!$otpRecord) {
    echo json_encode([
        'success' => false,
        'message' => 'No OTP found for this email',
        'error_code' => 'OTP_NOT_FOUND'
    ]);
    exit;
}

// Check if OTP is already used
if ($otpRecord['used']) {
    echo json_encode([
        'success' => false,
        'message' => 'OTP already used',
        'error_code' => 'OTP_ALREADY_USED'
    ]);
    exit;
}

// Check if OTP is expired (5 minutes)
$expiresAt = new DateTime($otpRecord['expires_at']);
$now = new DateTime();

if ($now > $expiresAt) {
    echo json_encode([
        'success' => false,
        'message' => 'OTP expired',
        'error_code' => 'OTP_EXPIRED'
    ]);
    exit;
}

// Check attempts limit (max 3)
if ($otpRecord['attempts'] >= 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Maximum OTP attempts exceeded',
        'error_code' => 'MAX_ATTEMPTS_EXCEEDED'
    ]);
    exit;
}

// Verify OTP
if (!password_verify($otp, $otpRecord['otp_hash'])) {
    // Increment attempts
    $newAttempts = $otpRecord['attempts'] + 1;
    updateOTPAttempts($email, $newAttempts);
    
    $remainingAttempts = 3 - $newAttempts;
    
    echo json_encode([
        'success' => false,
        'message' => 'Wrong OTP',
        'error_code' => 'WRONG_OTP',
        'remaining_attempts' => $remainingAttempts
    ]);
    exit;
}

// OTP is correct - mark as used
markOTPAsUsed($email);

// Success response
echo json_encode([
    'success' => true,
    'message' => 'OTP verified successfully',
    'email' => $email,
    'verified_at' => date('c')
]);
?>
