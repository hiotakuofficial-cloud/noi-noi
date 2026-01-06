<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, authkey, authkey2, babeer, apikey, user-memory, user-prompt');

require_once __DIR__ . '/../../../service/auth.php';

// Validate authentication
if (!validateAuth()) {
    sendUnauthorized();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST method required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['massage'] ?? $input['message'] ?? '';

// Get user customization from headers
$userMemory = $_SERVER['HTTP_USER_MEMORY'] ?? '';
$userPrompt = $_SERVER['HTTP_USER_PROMPT'] ?? '';

// Validate user prompt length (250 character limit)
if (!validateUserPrompt($userPrompt)) {
    sendPromptLimitError();
}

// Validate user memory length (500 character limit)
if (!validateUserMemory($userMemory)) {
    sendMemoryLimitError();
}

if (empty($message)) {
    echo json_encode(['error' => 'Message required']);
    exit;
}

// Load environment variables
loadEnv(__DIR__ . '/../../../.env');

$API_KEY = $_ENV['API_KEY'] ?? '';
$MODEL = $_ENV['MODEL'] ?? 'blackboxai/openai/gpt-4o-2024-11-20';
$API_URL = $_ENV['API_URL'] ?? 'https://api.blackbox.ai/chat/completions';
$SYSTEM_PROMPT = $_ENV['SYSTEM_PROMPT'] ?? 'You are Hisu.';

// Build enhanced system prompt with user customization
$enhancedSystemPrompt = $SYSTEM_PROMPT;

if (!empty($userPrompt)) {
    $enhancedSystemPrompt = $userPrompt . "\n\nBase Personality: " . $SYSTEM_PROMPT;
}

if (!empty($userMemory)) {
    $enhancedSystemPrompt .= "\n\nUser Context/Memory: " . $userMemory;
}

// Add search tool capability
$enhancedSystemPrompt .= "\n\nYou have access to Hiotaku's anime search. When users ask about anime availability, use internal search to check.";

// Check if user is asking about anime availability
$isAnimeQuery = preg_match('/\b(anime|naruto|demon slayer|attack on titan|one piece|dragon ball|available|hiotaku|watch|stream|episode|mil jayega|hai kya)\b/i', $message);

$searchResults = null;
if ($isAnimeQuery) {
    $animeQuery = extractAnimeQuery($message);
    if ($animeQuery) {
        $searchResults = searchAnime($animeQuery);
    }
}

// Function to extract anime name from user message
function extractAnimeQuery($message) {
    $animeNames = [
        'naruto', 'demon slayer', 'attack on titan', 'one piece', 'dragon ball',
        'my hero academia', 'jujutsu kaisen', 'bleach', 'death note', 'tokyo ghoul'
    ];
    
    $message = strtolower($message);
    foreach ($animeNames as $anime) {
        if (strpos($message, $anime) !== false) {
            return str_replace(' ', '-', $anime);
        }
    }
    
    if (preg_match('/"([^"]+)"/', $message, $matches)) {
        return str_replace(' ', '-', strtolower($matches[1]));
    }
    
    return null;
}

// Function to search anime
function searchAnime($query) {
    try {
        $searchUrl = 'http://localhost:8000/hiotaku/api/v1/chat/b/tool/search_tool.php';
        
        $postData = json_encode(['query' => $query, 'source' => 'all']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'key: hisu-hitaku'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Back to 10 seconds
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            return json_decode($response, true);
        }
        
        // Return timeout response for AI to handle
        return ['timeout' => true, 'query' => $query];
        
    } catch (Exception $e) {
        return ['timeout' => true, 'query' => $query];
    }
}

// Add search results to system prompt
if ($searchResults && isset($searchResults['timeout'])) {
    $enhancedSystemPrompt .= "\n\nSearch timeout occurred for '{$searchResults['query']}'. Respond with: 'Bhai khud search kar le, main nahi karunga! 😤 Server slow hai aaj. Direct Hiotaku pe ja kar dekh le!'";
} elseif ($searchResults && $searchResults['success']) {
    $enhancedSystemPrompt .= "\n\nSearch Results: Found {$searchResults['total']} results for '{$searchResults['query']}' on Hiotaku. ";
    if ($searchResults['total'] > 0) {
        $titles = array_slice(array_column($searchResults['data'], 'title'), 0, 3);
        $enhancedSystemPrompt .= "Available: " . implode(', ', $titles);
        if ($searchResults['total'] > 3) {
            $enhancedSystemPrompt .= " and " . ($searchResults['total'] - 3) . " more.";
        }
    } else {
        $enhancedSystemPrompt .= "Not available on Hiotaku.";
    }
}

if (empty($API_KEY)) {
    echo json_encode(['error' => 'API key not configured']);
    exit;
}

// Prepare request
$data = [
    'model' => $MODEL,
    'messages' => [
        ['role' => 'system', 'content' => $enhancedSystemPrompt],
        ['role' => 'user', 'content' => $message]
    ],
    'max_tokens' => 200,
    'temperature' => 0.7
];

$headers = [
    'Authorization: Bearer ' . $API_KEY,
    'Content-Type: application/json',
    'User-Agent: BlackboxCLI/1.0'
];

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $API_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    echo json_encode(['error' => 'API request failed']);
    exit;
}

$result = json_decode($response, true);

if (!$result || !isset($result['choices'][0]['message']['content'])) {
    echo json_encode(['error' => 'Invalid API response']);
    exit;
}

echo json_encode([
    'success' => true,
    'response' => trim($result['choices'][0]['message']['content']),
    'model' => $MODEL,
    'auth' => 'verified',
    'user_prompt_length' => strlen($userPrompt),
    'user_memory_length' => strlen($userMemory)
]);
?>
