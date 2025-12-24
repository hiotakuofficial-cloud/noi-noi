<?php
// Hindi API wrapper with renamed functions
function hindi_getCacheKey($url) { return md5($url); }
function hindi_getCache($key) {
    $cache_dir = '/tmp/animix_cache/';
    $file = $cache_dir . $key;
    if (file_exists($file) && (time() - filemtime($file)) < 86400) {
        return file_get_contents($file);
    }
    return false;
}
function hindi_setCache($key, $data) {
    $cache_dir = '/tmp/animix_cache/';
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);
    file_put_contents($cache_dir . $key, $data);
}

function searchHindi($query) {
    $searchUrl = "https://animixstream.com/search?q=" . urlencode($query);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'timeout' => 5
        ]
    ]);
    
    $html = @file_get_contents($searchUrl, false, $context);
    $results = [];
    
    if ($html) {
        // Simple regex to find anime data
        if (preg_match_all('/data-id="(\d+)".*?title="([^"]*)".*?src="([^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (stripos($match[2], $query) !== false) {
                    $results[] = [
                        'id' => $match[1],
                        'title' => $match[2] . ' Hindi Dubbed',
                        'thumbnail' => $match[3]
                    ];
                }
            }
        }
    }
    
    return $results;
}
?>
