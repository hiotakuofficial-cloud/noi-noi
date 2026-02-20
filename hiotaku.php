<?php
session_start();

// Configuration
define('SUPABASE_URL', 'https://brwzqawoncblbxqoqyua.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MjMzMzUyMiwiZXhwIjoyMDc3OTA5NTIyfQ.KQwe6q9hgb-QvAuVGwLyEzIO9a7cTXFFrYMzlayO89A');
define('ADMIN_PASSWORD', 'nehubaby7890');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Rate limiting function
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_key = 'login_attempts_' . $ip;
    $lockout_key = 'lockout_until_' . $ip;
    
    // Check if IP is locked out
    if (isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > time()) {
        $remaining = ceil(($_SESSION[$lockout_key] - time()) / 60);
        return ['locked' => true, 'minutes' => $remaining];
    }
    
    // Clean expired lockout
    if (isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] <= time()) {
        unset($_SESSION[$lockout_key]);
        unset($_SESSION[$attempts_key]);
    }
    
    return ['locked' => false];
}

function recordFailedAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_key = 'login_attempts_' . $ip;
    $lockout_key = 'lockout_until_' . $ip;
    
    if (!isset($_SESSION[$attempts_key])) {
        $_SESSION[$attempts_key] = 0;
    }
    
    $_SESSION[$attempts_key]++;
    
    if ($_SESSION[$attempts_key] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION[$lockout_key] = time() + LOCKOUT_TIME;
        return true; // Locked out
    }
    
    return false;
}

function getRemainingAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_key = 'login_attempts_' . $ip;
    $attempts = $_SESSION[$attempts_key] ?? 0;
    return MAX_LOGIN_ATTEMPTS - $attempts;
}

// Handle login
if (isset($_POST['login'])) {
    $rateLimit = checkRateLimit();
    
    if ($rateLimit['locked']) {
        $login_error = 'Too many failed attempts. Try again in ' . $rateLimit['minutes'] . ' minutes.';
    } else {
        sleep(2); // Slow down brute force attempts
        
        if ($_POST['password'] === ADMIN_PASSWORD) {
            // Reset attempts on successful login
            $ip = $_SERVER['REMOTE_ADDR'];
            unset($_SESSION['login_attempts_' . $ip]);
            unset($_SESSION['lockout_until_' . $ip]);
            
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $locked = recordFailedAttempt();
            
            if ($locked) {
                $login_error = 'Too many failed attempts. Account locked for 15 minutes.';
            } else {
                $remaining = getRemainingAttempts();
                $login_error = 'Invalid password. ' . $remaining . ' attempts remaining.';
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check authentication
if (!isset($_SESSION['authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Update Manager</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
            
            * {
                font-family: 'Poppins', sans-serif;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                background: #0a0a0a;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }
            
            body::before {
                content: '';
                position: fixed;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: 
                    radial-gradient(circle at 30% 40%, rgba(255, 140, 0, 0.15) 0%, transparent 40%),
                    radial-gradient(circle at 70% 60%, rgba(255, 119, 0, 0.12) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(255, 140, 0, 0.08) 0%, transparent 50%);
                animation: rotate 20s linear infinite;
            }
            
            @keyframes rotate {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .login-container {
                position: relative;
                z-index: 1;
                width: 100%;
                max-width: 450px;
                padding: 20px;
            }
            
            .login-card {
                background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(30, 30, 30, 0.95) 100%);
                border: 1px solid rgba(255, 140, 0, 0.3);
                border-radius: 25px;
                padding: 50px 40px;
                box-shadow: 
                    0 20px 60px rgba(0, 0, 0, 0.5),
                    0 0 0 1px rgba(255, 140, 0, 0.2),
                    inset 0 1px 0 rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .logo-container {
                text-align: center;
                margin-bottom: 40px;
            }
            
            .logo-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%);
                border-radius: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                color: white;
                box-shadow: 0 10px 30px rgba(255, 140, 0, 0.4);
                margin-bottom: 20px;
                animation: pulse 2s ease-in-out infinite;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .login-card h2 {
                color: #fff;
                margin-bottom: 10px;
                font-weight: 700;
                font-size: 1.8rem;
                text-align: center;
                letter-spacing: -0.5px;
            }
            
            .login-card p {
                color: #888;
                text-align: center;
                margin-bottom: 35px;
                font-size: 0.95rem;
            }
            
            .form-label {
                color: #ddd;
                font-weight: 500;
                margin-bottom: 10px;
                font-size: 0.9rem;
            }
            
            .input-group {
                position: relative;
                margin-bottom: 25px;
            }
            
            .input-icon {
                position: absolute;
                left: 18px;
                top: 50%;
                transform: translateY(-50%);
                color: #FF8C00;
                font-size: 1.2rem;
                z-index: 2;
            }
            
            .form-control {
                background: rgba(255, 255, 255, 0.03);
                border: 2px solid rgba(255, 255, 255, 0.08);
                color: #fff;
                padding: 16px 20px 16px 50px;
                border-radius: 15px;
                font-size: 0.95rem;
                transition: all 0.3s ease;
            }
            
            .form-control:focus {
                background: rgba(255, 255, 255, 0.05);
                border-color: #FF8C00;
                color: #fff;
                box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.15), 0 5px 20px rgba(255, 140, 0, 0.2);
                outline: none;
            }
            
            .form-control::placeholder {
                color: #555;
            }
            
            .btn-login {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%);
                border: none;
                border-radius: 15px;
                color: white;
                font-weight: 600;
                font-size: 1rem;
                letter-spacing: 0.5px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 8px 25px rgba(255, 140, 0, 0.3);
                margin-top: 10px;
            }
            
            .btn-login:hover {
                background: linear-gradient(135deg, #ff7700 0%, #ff6600 100%);
                transform: translateY(-3px);
                box-shadow: 0 12px 35px rgba(255, 140, 0, 0.5);
            }
            
            .btn-login:active {
                transform: translateY(-1px);
            }
            
            .alert {
                border-radius: 15px;
                border: none;
                padding: 16px 20px;
                font-weight: 500;
                margin-bottom: 25px;
                backdrop-filter: blur(10px);
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
            
            .alert-danger {
                background: linear-gradient(135deg, rgba(255, 56, 56, 0.2) 0%, rgba(255, 23, 68, 0.2) 100%);
                border-left: 4px solid #ff3838;
                color: #ff8a8a;
            }
            
            .security-badge {
                text-align: center;
                margin-top: 30px;
                padding-top: 25px;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            .security-badge i {
                color: #FF8C00;
                font-size: 1.5rem;
                margin-bottom: 10px;
            }
            
            .security-badge p {
                color: #666;
                font-size: 0.85rem;
                margin: 0;
            }
            
            @media (max-width: 576px) {
                .login-card {
                    padding: 40px 30px;
                }
                
                .login-card h2 {
                    font-size: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-card">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h2>Update Manager</h2>
                    <p>Secure access to Hiotaku admin panel</p>
                </div>
                
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($login_error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Admin Password</label>
                        <div class="input-group">
                            <i class="bi bi-key-fill input-icon"></i>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required autofocus>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login to Dashboard
                    </button>
                </form>
                
                <div class="security-badge">
                    <i class="bi bi-shield-check"></i>
                    <p>Protected by rate limiting & brute force protection</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// API Handler
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    function supabaseRequest($method, $endpoint, $data = null) {
        $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $httpCode, 'data' => json_decode($response, true)];
    }
    
    // Get all updates
    if ($_GET['api'] === 'list') {
        $result = supabaseRequest('GET', 'updates?select=*&order=created_at.desc');
        echo json_encode($result);
        exit;
    }
    
    // Add new update
    if ($_GET['api'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Server-side validation
        if (empty($input['version']) || empty($input['update_name']) || empty($input['link']) || empty($input['description'])) {
            echo json_encode(['code' => 400, 'error' => 'All fields are required']);
            exit;
        }
        
        if (strlen($input['description']) > 500) {
            echo json_encode(['code' => 400, 'error' => 'Description must be 500 characters or less']);
            exit;
        }
        
        if (!filter_var($input['link'], FILTER_VALIDATE_URL)) {
            echo json_encode(['code' => 400, 'error' => 'Invalid URL format']);
            exit;
        }
        
        $data = [
            'version' => trim($input['version']),
            'update_name' => trim($input['update_name']),
            'link' => trim($input['link']),
            'description' => trim($input['description'])
        ];
        
        $result = supabaseRequest('POST', 'updates', $data);
        
        // Send notification to all users if update added successfully
        if ($result['code'] === 201) {
            try {
                // Load notification sender class
                require_once __DIR__ . '/notification/config/config.php';
                require_once __DIR__ . '/notification/classes/NotificationSender.php';
                
                $sender = new NotificationSender();
                $notifResult = $sender->sendToAllUsers([
                    'title' => 'Hiotaku New Update Has Been Released',
                    'body' => $data['update_name'],
                    'type' => 'update',
                    'data' => [
                        'source' => 'update_manager',
                        'timestamp' => date('c'),
                        'notification_type' => 'update',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => '/main'
                    ]
                ]);
                
                $result['notification_sent'] = $notifResult['success'];
                $result['notification_response'] = $notifResult;
                
            } catch (Exception $e) {
                $result['notification_sent'] = false;
                $result['notification_error'] = $e->getMessage();
                error_log("Notification Error: " . $e->getMessage());
            }
        }
        
        echo json_encode($result);
        exit;
    }
    
    // Delete update
    if ($_GET['api'] === 'delete' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['version'])) {
            echo json_encode(['code' => 400, 'error' => 'Version is required']);
            exit;
        }
        
        $result = supabaseRequest('DELETE', 'updates?version=eq.' . urlencode($input['version']));
        echo json_encode($result);
        exit;
    }
    
    echo json_encode(['code' => 404, 'error' => 'Invalid API endpoint']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Manager - Hiotaku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            padding: 0;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 140, 0, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 119, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 140, 0, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        .header {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.95) 0%, rgba(255, 119, 0, 0.95) 100%);
            padding: 40px 0;
            margin-bottom: 50px;
            box-shadow: 0 10px 40px rgba(255, 140, 0, 0.3);
            backdrop-filter: blur(20px);
            border-bottom: 2px solid rgba(255, 140, 0, 0.5);
        }
        
        .header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }
        
        .header p {
            font-weight: 300;
            font-size: 1.1rem;
            opacity: 0.95;
        }
        
        .card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            border: 1px solid rgba(255, 140, 0, 0.2);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 140, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(255, 140, 0, 0.2), 0 0 0 1px rgba(255, 140, 0, 0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.15) 0%, rgba(255, 119, 0, 0.1) 100%);
            border-bottom: 2px solid rgba(255, 140, 0, 0.3);
            color: #FF8C00;
            font-weight: 600;
            padding: 25px 30px;
            font-size: 1.3rem;
            letter-spacing: -0.3px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 500;
            color: #ddd;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: #FF8C00;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.15), 0 5px 20px rgba(255, 140, 0, 0.2);
            transform: translateY(-2px);
        }
        
        .form-control::placeholder { 
            color: #666;
        }
        
        textarea.form-control {
            resize: none;
        }
        
        .btn {
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            letter-spacing: 0.3px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff7700 0%, #ff6600 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 140, 0, 0.5);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff3838 0%, #ff1744 100%);
            box-shadow: 0 5px 15px rgba(255, 56, 56, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #ff1744 0%, #e60023 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 56, 56, 0.5);
        }
        
        .table {
            color: #fff;
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.15) 0%, rgba(255, 119, 0, 0.1) 100%);
            border-bottom: 2px solid rgba(255, 140, 0, 0.4);
        }
        
        .table thead th {
            padding: 18px 15px;
            font-weight: 600;
            color: #FF8C00;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: rgba(255, 140, 0, 0.08);
            transform: scale(1.01);
        }
        
        .badge {
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }
        
        .badge.bg-primary {
            background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%) !important;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
        }
        
        .char-counter {
            font-size: 0.85rem;
            color: #888;
            float: right;
            font-weight: 500;
        }
        
        .char-counter.warning { 
            color: #ffc107;
            font-weight: 600;
        }
        
        .char-counter.danger { 
            color: #ff3838;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 18px 25px;
            font-weight: 500;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(32, 201, 151, 0.2) 100%);
            border-left: 4px solid #28a745;
            color: #5dff9f;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(255, 56, 56, 0.2) 0%, rgba(255, 23, 68, 0.2) 100%);
            border-left: 4px solid #ff3838;
            color: #ff8a8a;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
            border-left: 4px solid #ffc107;
            color: #ffd966;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
        }
        
        .text-warning {
            color: #FF8C00 !important;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
        }
        
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        
        a {
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .table thead th,
            .table tbody td {
                padding: 12px 10px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-0"><i class="bi bi-cloud-arrow-down"></i> Update Manager</h1>
                    <p class="mb-0 mt-2 opacity-75">Manage Hiotaku app updates</p>
                </div>
                <a href="?logout" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Add Update Form -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Update
            </div>
            <div class="card-body">
                <form id="addUpdateForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Version <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="version" placeholder="e.g., 1.0.0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Update Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="update_name" placeholder="e.g., Major Update" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Download Link <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="link" placeholder="https://example.com/app.apk" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            Description <span class="text-danger">*</span>
                            <span class="char-counter" id="charCounter">0/500</span>
                        </label>
                        <textarea class="form-control" id="description" rows="4" maxlength="500" placeholder="Enter update description..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Updates Table -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> All Updates
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Update Name</th>
                                <th>Link</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="updatesTable">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-warning" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Character counter
        const descInput = document.getElementById('description');
        const charCounter = document.getElementById('charCounter');
        
        descInput.addEventListener('input', function() {
            const count = this.value.length;
            charCounter.textContent = `${count}/500`;
            charCounter.classList.remove('warning', 'danger');
            if (count > 450) charCounter.classList.add('danger');
            else if (count > 400) charCounter.classList.add('warning');
        });

        // Show alert
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.getElementById('alertContainer').appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        // Load updates
        async function loadUpdates() {
            try {
                const response = await fetch('?api=list');
                const result = await response.json();
                
                if (result.code === 200 && result.data) {
                    const tbody = document.getElementById('updatesTable');
                    
                    if (result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No updates found</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = result.data.map(update => `
                        <tr>
                            <td><span class="badge bg-primary">${escapeHtml(update.version)}</span></td>
                            <td>${escapeHtml(update.update_name)}</td>
                            <td><a href="${escapeHtml(update.link)}" target="_blank" class="text-warning text-decoration-none">
                                <i class="bi bi-link-45deg"></i> Download
                            </a></td>
                            <td>${escapeHtml(update.description.substring(0, 100))}${update.description.length > 100 ? '...' : ''}</td>
                            <td>${new Date(update.created_at).toLocaleString()}</td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="deleteUpdate('${escapeHtml(update.version)}')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    throw new Error('Failed to load updates');
                }
            } catch (error) {
                showAlert('Error loading updates: ' + error.message, 'danger');
            }
        }

        // Add update
        document.getElementById('addUpdateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            
            const data = {
                version: document.getElementById('version').value.trim(),
                update_name: document.getElementById('update_name').value.trim(),
                link: document.getElementById('link').value.trim(),
                description: document.getElementById('description').value.trim()
            };
            
            // Client-side validation
            if (!data.version || !data.update_name || !data.link || !data.description) {
                showAlert('All fields are required', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            if (data.description.length > 500) {
                showAlert('Description must be 500 characters or less', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            try {
                const response = await fetch('?api=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.code === 201) {
                    let message = '✅ Update added successfully!';
                    
                    if (result.notification_sent) {
                        message += ' Notification sent to all users.';
                    } else if (result.notification_response) {
                        message += ' ⚠️ Update added but notification failed: ' + (result.notification_response.error || 'Unknown error');
                    } else {
                        message += ' ⚠️ Update added but notification status unknown.';
                    }
                    
                    showAlert(message, result.notification_sent ? 'success' : 'warning');
                    this.reset();
                    charCounter.textContent = '0/500';
                    loadUpdates();
                } else if (result.code === 409) {
                    showAlert('Version already exists', 'warning');
                } else {
                    showAlert(result.error || 'Failed to add update', 'danger');
                }
            } catch (error) {
                showAlert('Error: ' + error.message, 'danger');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        // Delete update
        async function deleteUpdate(version) {
            if (!confirm(`Are you sure you want to delete version ${version}?`)) {
                return;
            }
            
            try {
                const response = await fetch('?api=delete', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ version })
                });
                
                const result = await response.json();
                
                if (result.code === 200 || result.code === 204) {
                    showAlert('Update deleted successfully!', 'success');
                    loadUpdates();
                } else {
                    showAlert('Failed to delete update', 'danger');
                }
            } catch (error) {
                showAlert('Error: ' + error.message, 'danger');
            }
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load updates on page load
        loadUpdates();
    </script>
</body>
</html>
