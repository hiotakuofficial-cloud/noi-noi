<?php
header('Content-Type: application/json');

function fail($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// --------- Validate Input ---------
if (!isset($_GET['action']) || $_GET['action'] !== 'url') {
    fail('invalid action: expected action=url');
}

if (!isset($_GET['url']) || empty($_GET['url'])) {
    fail('no url provided');
}

$insta_url = trim($_GET['url']);

if (strpos($insta_url, 'instagram.com') === false) {
    fail('not an instagram link');
}

// --------- API Request with cURL ---------
if (!function_exists('curl_init')) {
    fail('curl not available');
}

$data = json_encode(['url' => $insta_url]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://thesocialcat.com/api/instagram-download',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Origin: https://thesocialcat.com',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    fail('request failed: ' . $error);
}

if ($http_code !== 200) {
    fail('api error: HTTP ' . $http_code);
}

// --------- Parse Response ---------
$result = json_decode($response, true);

if (!$result) {
    fail('invalid json response');
}

echo json_encode([
    'success' => true,
    'username' => $result['username'] ?? '',
    'caption' => $result['caption'] ?? '',
    'thumbnail' => $result['thumbnail'] ?? '',
    'download_links' => $result['mediaUrls'] ?? []
]);
?>
