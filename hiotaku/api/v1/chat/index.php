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
if ($isAnimeQuery) { // Re-enabled with shorter timeout
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

// Function to get poster URL from details API
function getPosterUrl($animeId, $source) {
    try {
        $url = 'https://anime-check-api.hiotaku-official.workers.dev/';
        $type = $source === 'hindi' ? 'hindi' : 'english';
        $data = [
            'password' => 'nehubaby7890',
            'action' => 'details',
            'id' => $animeId,
            'type' => $type
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['thumbnail'])) {
                return $data['data']['thumbnail'];
            }
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to search anime directly
function searchAnime($query) {
    try {
        // Call Cloudflare Workers API directly
        $url = 'https://anime-check-api.hiotaku-official.workers.dev/';
        $data = [
            'password' => 'nehubaby7890',
            'action' => 'check',
            'query' => $query
        ];
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $responseData = json_decode($response, true);
            
            // Convert to expected format
            $results = [];
            if (isset($responseData['hindi']['available']) && $responseData['hindi']['available']) {
                foreach ($responseData['hindi']['results'] as $item) {
                    $item['source'] = 'hindi';
                    $results[] = $item;
                }
            }
            if (isset($responseData['english']['available']) && $responseData['english']['available']) {
                foreach ($responseData['english']['results'] as $item) {
                    $item['source'] = 'main';
                    $results[] = $item;
                }
            }
            
            // Simple anime cards with basic info
            $animeCards = [];
            foreach (array_slice($results, 0, 5) as $item) {
                $animeCards[] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'source' => $item['source']
                ];
            }
            
            return [
                'success' => true,
                'query' => $query,
                'total' => count($results),
                'data' => $results,
                'anime_cards' => $animeCards
            ];
        }
        
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
        $enhancedSystemPrompt .= " Respond enthusiastically that these anime are available on Hiotaku platform!";
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

// Make API request using file_get_contents
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => json_encode($data),
        'timeout' => 20,
        'ignore_errors' => true
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($API_URL, false, $context);
$httpCode = 200; // Assume success if response received

if ($response === false) {
    $httpCode = 500;
}

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
    'anime_cards' => $searchResults['anime_cards'] ?? [],
    'model' => $MODEL,
    'auth' => 'verified',
    'user_prompt_length' => strlen($userPrompt),
    'user_memory_length' => strlen($userMemory)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
