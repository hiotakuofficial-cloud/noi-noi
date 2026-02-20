<?php
// Security functions
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) return [];
    
    $env = [];
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
    return $env;
}

function validateAuth($headers, $env) {
    $key1 = $headers['HTTP_KEY1'] ?? '';
    $key2 = $headers['HTTP_KEY2'] ?? '';
    $token = $headers['HTTP_TOKEN'] ?? '';
    
    return ($key1 === $env['KEY1'] && $key2 === $env['KEY2'] && $token === $env['TOKEN']);
}

function validateJson($input) {
    return is_array($input) && json_last_error() === JSON_ERROR_NONE;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function makeAPICall($url, $data, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'code' => $httpCode];
}
?>
