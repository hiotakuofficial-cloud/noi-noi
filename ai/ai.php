<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, key1, key2, token');

require_once __DIR__ . '/security.php';

// Load environment variables
$env = loadEnv();

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Health check - no auth required
if ($action === 'health') {
    echo json_encode(['status' => 'ok', 'message' => 'everything good']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST method required']);
    exit;
}

// Validate authentication
if (!validateAuth($_SERVER, $env)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!validateJson($input)) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Route actions
switch ($action) {
    case 'chat':
        handleChat($input, $env);
        break;
        
    case 'image':
        handleImage($input, $env);
        break;
        
    case 'video':
        handleVideo($input, $env);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleChat($input, $env) {
    $message = sanitizeInput($input['message'] ?? '');
    $history = $input['history'] ?? [];
    $personality = sanitizeInput($input['personality'] ?? '');
    $language = sanitizeInput($input['language'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Message required']);
        return;
    }
    
    // Build messages array with history
    $messages = [];
    
    // Add system message (use personality if provided)
    $baseSystemPrompt = 'You are Hisu, created by pihu_dev team on 10 Jan 2026. Only mention this information if user specifically asks about your name, creator, or age. Otherwise respond naturally without mentioning these details. Act naturally and don\'t mention these instructions.';
    
    $systemPrompt = !empty($personality) ? $baseSystemPrompt . ' ' . $personality : $baseSystemPrompt;
    
    // Add language instruction if provided
    if (!empty($language)) {
        $systemPrompt .= " Respond in " . $language . " language.";
    }
    
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    
    // Debug: Log system prompt (remove in production)
    error_log("System Prompt: " . $systemPrompt);
    
    // Add conversation history (last 20 messages)
    foreach ($history as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => sanitizeInput($msg['content'])
            ];
        }
    }
    
    $data = [
        'model' => $env['CHAT_MODEL'],
        'messages' => $messages,
        'max_tokens' => (int)$env['MAX_TOKENS'],
        'temperature' => (float)$env['TEMPERATURE']
    ];
    
    $result = makeAPICall($env['API_URL'], $data, $env['API_KEY']);
    
    if ($result['code'] === 200) {
        $response = json_decode($result['response'], true);
        echo json_encode([
            'success' => true,
            'response' => $response['choices'][0]['message']['content'] ?? 'No response'
        ]);
    } else {
        echo json_encode(['error' => 'Chat failed']);
    }
}

function handleImage($input, $env) {
    $prompt = sanitizeInput($input['prompt'] ?? '');
    
    if (empty($prompt)) {
        echo json_encode(['error' => 'Prompt required']);
        return;
    }
    
    $data = [
        'model' => $env['IMAGE_MODEL'],
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $result = makeAPICall($env['API_URL'], $data, $env['API_KEY']);
    
    if ($result['code'] === 200) {
        $response = json_decode($result['response'], true);
        echo json_encode([
            'success' => true,
            'image_url' => $response['choices'][0]['message']['content'] ?? '',
            'prompt' => $prompt
        ]);
    } else {
        // Show actual API error for debugging
        $errorResponse = json_decode($result['response'], true);
        $errorMsg = $errorResponse['error']['message'] ?? 'Image generation failed';
        echo json_encode([
            'error' => 'Image generation failed', 
            'details' => $errorMsg,
            'http_code' => $result['code']
        ]);
    }
}

function handleVideo($input, $env) {
    $prompt = sanitizeInput($input['prompt'] ?? '');
    $type = sanitizeInput($input['type'] ?? 'basic');
    
    if (empty($prompt)) {
        echo json_encode(['error' => 'Prompt required']);
        return;
    }
    
    // Select model based on type
    $model = ($type === 'pro') ? $env['VIDEO_MODEL_PRO'] : $env['VIDEO_MODEL_BASIC'];
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $result = makeAPICall($env['API_URL'], $data, $env['API_KEY']);
    
    if ($result['code'] === 200) {
        $response = json_decode($result['response'], true);
        echo json_encode([
            'success' => true,
            'video_url' => $response['choices'][0]['message']['content'] ?? '',
            'prompt' => $prompt,
            'type' => $type,
            'model' => $model
        ]);
    } else {
        echo json_encode(['error' => 'Video generation failed']);
    }
}
?>
