<?php
require_once '../../auth.php';
require_once '../src/PHPMailer.php';
require_once '../src/SMTP.php';
require_once '../src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
loadEnv('../.env');

// Verify token first
$tokenCheck = verifyToken();
if (!$tokenCheck['success']) {
    echo json_encode($tokenCheck);
    exit;
}

// Get email from query parameter
$email = $_GET['gmail'] ?? '';

if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email parameter required',
        'error_code' => 'MISSING_EMAIL'
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

// Generate 6-digit OTP
$otp = sprintf('%06d', mt_rand(0, 999999));
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

// Database connection (Supabase)
function saveOTP($email, $otpHash) {
    $supabaseUrl = $_ENV['SUPAURL'];
    $supabaseKey = $_ENV['ANNOYKEY'];
    
    $data = [
        'email' => $email,
        'otp_hash' => $otpHash,
        'created_at' => date('c'),
        'expires_at' => date('c', strtotime('+5 minutes')),
        'attempts' => 0,
        'last_sent_at' => date('c'),
        'used' => false
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/rest/v1/otps');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: resolution=merge-duplicates'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

// Send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['GMAIL'];
        $mail->Password = $_ENV['PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        
        // Recipients
        $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Hiotaku Verification Code';
        
        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Hiotaku - Verification Code</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #0F0F23 0%, #1A1A2E 100%); }
                .container { max-width: 600px; margin: 0 auto; background: #fff; }
                .header { background: linear-gradient(135deg, #FF4444 0%, #FF6B6B 100%); padding: 30px; text-align: center; }
                .logo { color: white; font-size: 28px; font-weight: bold; margin: 0; }
                .content { padding: 40px 30px; text-align: center; }
                .otp-box { background: #f8f9fa; border: 2px dashed #FF4444; border-radius: 12px; padding: 30px; margin: 30px 0; }
                .otp-code { font-size: 36px; font-weight: bold; color: #FF4444; letter-spacing: 8px; margin: 10px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                .anime-icons { font-size: 24px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 class="logo">🎌 HIOTAKU</h1>
                    <p style="color: white; margin: 10px 0 0 0;">Your Anime Streaming Platform</p>
                </div>
                
                <div class="content">
                    <div class="anime-icons">🍜 🎭 ⚔️ 🌸 🎌</div>
                    
                    <h2 style="color: #333; margin-bottom: 20px;">Verification Code</h2>
                    <p style="color: #666; font-size: 16px; line-height: 1.6;">
                        Welcome to Hiotaku! Use the verification code below to complete your authentication:
                    </p>
                    
                    <div class="otp-box">
                        <p style="margin: 0; color: #333; font-weight: bold;">Your OTP Code</p>
                        <div class="otp-code">' . $otp . '</div>
                        <p style="margin: 0; color: #666; font-size: 14px;">Valid for 5 minutes</p>
                    </div>
                    
                    <p style="color: #666; font-size: 14px; line-height: 1.6;">
                        If you didn\'t request this code, please ignore this email.<br>
                        Never share your OTP with anyone for security reasons.
                    </p>
                </div>
                
                <div class="footer">
                    <p>© 2024 Hiotaku - Premium Anime Streaming</p>
                    <p>Enjoy unlimited anime with friends! 🎌</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $htmlBody;
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Save OTP to database
if (!saveOTP($email, $otpHash)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save OTP',
        'error_code' => 'DATABASE_ERROR'
    ]);
    exit;
}

// Send OTP email
if (!sendOTPEmail($email, $otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send OTP email',
        'error_code' => 'EMAIL_ERROR'
    ]);
    exit;
}

// Success response
echo json_encode([
    'success' => true,
    'message' => 'OTP sent successfully',
    'email' => $email,
    'expires_in' => '5 minutes'
]);
?>
