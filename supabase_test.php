<?php

// Supabase Connection Test
$supabaseUrl = 'https://brwzqawoncblbxqoqyua.supabase.co';
$serviceRoleKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MjMzMzUyMiwiZXhwIjoyMDc3OTA5NTIyfQ.KQwe6q9hgb-QvAuVGwLyEzIO9a7cTXFFrYMzlayO89A';

function makeSupabaseRequest($url, $method = 'GET', $data = null) {
    global $supabaseUrl, $serviceRoleKey;
    
    $headers = [
        'Authorization: Bearer ' . $serviceRoleKey,
        'apikey: ' . $serviceRoleKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

// Test connection
echo "🔗 Testing Supabase Connection...\n\n";

// Test 1: Check project info
$result = makeSupabaseRequest('/rest/v1/');
echo "📊 Project Status: " . ($result['status'] == 200 ? "✅ Connected" : "❌ Failed") . "\n";

// Test 2: List existing buckets
$buckets = makeSupabaseRequest('/storage/v1/bucket');
echo "🗂️  Existing Buckets: " . ($buckets['status'] == 200 ? "✅ Accessible" : "❌ Failed") . "\n";

if ($buckets['status'] == 200 && !empty($buckets['data'])) {
    echo "   Current buckets:\n";
    foreach ($buckets['data'] as $bucket) {
        echo "   - " . $bucket['name'] . "\n";
    }
} else {
    echo "   No buckets found or error accessing storage\n";
}

// Test 3: Check auth
$auth = makeSupabaseRequest('/auth/v1/settings');
echo "🔐 Auth Status: " . ($auth['status'] == 200 ? "✅ Working" : "❌ Failed") . "\n";

echo "\n🚀 Supabase Connection Ready!\n";
echo "📋 Available Operations:\n";
echo "   - Create buckets\n";
echo "   - Setup storage policies\n";
echo "   - Manage files\n";
echo "   - Database operations\n";

?>
