<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Token Authentication
require_once '../auth.php';
verifyApiToken();

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

$action = $_GET['action'];

if ($action === 'url') {
    if (!isset($_GET['url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing url parameter']);
        exit;
    }
    
    $url = $_GET['url'];
    
    // Extract video ID
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches);
    if (!$matches) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid YouTube URL']);
        exit;
    }
    
    $videoId = $matches[1];
    
    try {
        // Get video info from YouTube
        $videoInfo = file_get_contents("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json");
        $videoData = json_decode($videoInfo, true);
        
        // Available qualities
        $qualities = [
            'mp4' => [
                ['quality' => '720', 'label' => '720p MP4'],
                ['quality' => '480', 'label' => '480p MP4'],
                ['quality' => '360', 'label' => '360p MP4']
            ],
            'mp3' => [
                ['quality' => '128', 'label' => '128kbps MP3'],
                ['quality' => '320', 'label' => '320kbps MP3']
            ]
        ];
        
        $response = [
            'success' => true,
            'videoId' => $videoId,
            'title' => $videoData['title'] ?? 'Unknown Title',
            'thumbnail' => $videoData['thumbnail_url'] ?? '',
            'author' => $videoData['author_name'] ?? 'Unknown Author',
            'duration' => null,
            'qualities' => $qualities
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process request: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'download') {
    if (!isset($_GET['url']) || !isset($_GET['type']) || !isset($_GET['q'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing url, type, or q parameter']);
        exit;
    }
    
    $url = $_GET['url'];
    $type = $_GET['type'];
    $quality = $_GET['q'];
    
    // Extract video ID
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches);
    if (!$matches) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid YouTube URL']);
        exit;
    }
    
    $videoId = $matches[1];
    
    try {
        // Get API key
        $keyResponse = file_get_contents('https://cnv.cx/v2/sanity/key', false, stream_context_create([
            'http' => [
                'header' => [
                    'Referer: https://frame.y2meta-uk.com/',
                    'Origin: https://frame.y2meta-uk.com',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]
        ]));
        
        $keyData = json_decode($keyResponse, true);
        $key = $keyData['key'];
        
        // Prepare conversion data
        $postData = http_build_query([
            'link' => "https://youtu.be/{$videoId}",
            'format' => $type,
            'audioBitrate' => $type === 'mp3' ? $quality : '128',
            'videoQuality' => $type === 'mp4' ? $quality : '720',
            'filenameStyle' => 'pretty',
            'vCodec' => 'h264'
        ]);
        
        // Convert video
        $convertResponse = file_get_contents('https://cnv.cx/v2/converter', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Referer: https://frame.y2meta-uk.com/',
                    'Origin: https://frame.y2meta-uk.com',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    "key: {$key}"
                ],
                'content' => $postData
            ]
        ]));
        
        $convertData = json_decode($convertResponse, true);
        
        if ($convertData && $convertData['status'] === 'tunnel') {
            $response = [
                'success' => true,
                'download_url' => $convertData['url'],
                'filename' => $convertData['filename'],
                'type' => $type,
                'quality' => $quality
            ];
        } else {
            $response = [
                'success' => false,
                'error' => 'Conversion failed',
                'details' => $convertData
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process download: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action. Use "url" or "download"']);
}
?>
