<?php
// Load environment variables
function loadEnv($file) {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Security check - all four auth keys required
function validateAuth() {
    loadEnv(__DIR__ . '/../.env');
    
    $authkey = $_SERVER['HTTP_AUTHKEY'] ?? '';
    $authkey2 = $_SERVER['HTTP_AUTHKEY2'] ?? '';
    $babeer = $_SERVER['HTTP_BABEER'] ?? '';
    $apikey = $_SERVER['HTTP_APIKEY'] ?? '';
    
    if ($authkey !== $_ENV['AUTHKEY']) {
        return false;
    }
    
    if ($authkey2 !== $_ENV['AUTHKEY2']) {
        return false;
    }
    
    if ($babeer !== $_ENV['BABEER']) {
        return false;
    }
    
    if ($apikey !== $_ENV['APIKEY']) {
        return false;
    }
    
    return true;
}

// Validate user prompt length
function validateUserPrompt($userPrompt) {
    if (strlen($userPrompt) > 250) {
        return false;
    }
    return true;
}

// Validate user memory length
function validateUserMemory($userMemory) {
    if (strlen($userMemory) > 500) {
        return false;
    }
    return true;
}

// Send unauthorized response
function sendUnauthorized() {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Send user prompt limit error
function sendPromptLimitError() {
    echo json_encode(['error' => 'User prompt limit access denied']);
    exit;
}

// Send user memory limit error
function sendMemoryLimitError() {
    echo json_encode(['error' => 'User memory limit access denied']);
    exit;
}
?>
