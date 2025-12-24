<?php
header('Content-Type: application/json');

// Token validation
$validToken = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!hash_equals($validToken, $token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$who = $input['who'] ?? '';
$anime = $input['anime'] ?? '';
$tool = $input['tool'] ?? '';

if ($who !== 'im-hisu') {
    echo json_encode(['error' => 'model isnt working currently']);
    exit;
}

if ($tool === 'search-anime' && !empty($anime)) {
    $results = [];
    
    // English API results
    $_GET['action'] = 'search';
    $_GET['q'] = $anime;
    $_GET['token'] = $validToken;
    
    ob_start();
    include __DIR__ . '/../../../api.php';
    $englishOutput = ob_get_clean();
    
    if ($englishOutput) {
        $englishData = json_decode($englishOutput, true);
        if (isset($englishData['success']) && $englishData['success'] && !empty($englishData['results'])) {
            foreach ($englishData['results'] as $item) {
                $results[] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'poster' => $item['poster'],
                    'type' => 'english',
                    'source' => 'hiotaku'
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $anime,
        'total' => count($results),
        'results' => $results
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'Invalid request parameters']);
}
?>
