<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Token Authentication
require_once 'auth.php';
verifyApiToken();

// Firebase configuration
$firebaseConfig = [
    'apiKey' => 'AIzaSyD4woF0zLOwAO-oQxGfhj73KIgc7MrMpz4',
    'authDomain' => 'hiotaku-2.firebaseapp.com',
    'databaseURL' => 'https://hiotaku-2-default-rtdb.asia-southeast1.firebasedatabase.app',
    'projectId' => 'hiotaku-2',
    'storageBucket' => 'hiotaku-2.firebasestorage.app',
    'messagingSenderId' => '223204260133',
    'appId' => '1:223204260133:web:f2655423cc0fee4d0002cc'
];

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'check':
        checkUpdates();
        break;
    
    case 'update':
        updateApp();
        break;
    
    case 'push':
        pushNotification();
        break;
    
    case 'getnoti':
        getNotifications();
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use action=check, action=update, action=push, or action=getnoti'
        ]);
        break;
}

function checkUpdates() {
    global $firebaseConfig;
    
    try {
        // Firestore REST API URL
        $projectId = $firebaseConfig['projectId'];
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/updates/app_info";
        
        // Make GET request to Firestore
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch update information'
            ]);
            return;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['fields'])) {
            // Extract data from Firestore format
            $updateInfo = [
                'version' => $data['fields']['version']['stringValue'] ?? '1.0.0',
                'update_url' => $data['fields']['update_url']['stringValue'] ?? '',
                'changelog' => $data['fields']['changelog']['stringValue'] ?? '',
                'updated_at' => $data['fields']['updated_at']['stringValue'] ?? date('Y-m-d H:i:s')
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $updateInfo
            ]);
        } else {
            // No document found, return default
            echo json_encode([
                'success' => true,
                'data' => [
                    'version' => '1.0.0',
                    'update_url' => '',
                    'changelog' => 'No updates available',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching updates: ' . $e->getMessage()
        ]);
    }
}

function updateApp() {
    global $firebaseConfig;
    
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'No JSON data provided'
        ]);
        return;
    }
    
    // Validate required fields
    if (!isset($data['version']) || !isset($data['update_url']) || !isset($data['changelog'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: version, update_url, changelog'
        ]);
        return;
    }
    
    try {
        // Prepare Firestore document
        $updateInfo = [
            'fields' => [
                'version' => ['stringValue' => $data['version']],
                'update_url' => ['stringValue' => $data['update_url']],
                'changelog' => ['stringValue' => $data['changelog']],
                'updated_at' => ['stringValue' => date('Y-m-d H:i:s')]
            ]
        ];
        
        // Firestore REST API URL
        $projectId = $firebaseConfig['projectId'];
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/updates/app_info";
        
        // Make PATCH request to Firestore
        $context = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($updateInfo)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update information in Firestore'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Update information saved successfully to Firestore',
            'data' => [
                'version' => $data['version'],
                'update_url' => $data['update_url'],
                'changelog' => $data['changelog'],
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error saving to Firestore: ' . $e->getMessage()
        ]);
    }
}
function getNotifications() {
    global $firebaseConfig;
    
    try {
        $projectId = $firebaseConfig['projectId'];
        
        // Get all notifications from Firebase
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ]);
            return;
        }
        
        $data = json_decode($response, true);
        $notifications = [];
        
        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                // Skip counter document
                if (strpos($doc['name'], 'counter') !== false) {
                    continue;
                }
                
                $fields = $doc['fields'];
                
                // Format notification in required structure
                $notification = [
                    'id' => str_pad($fields['id']['integerValue'], 3, '0', STR_PAD_LEFT),
                    'title' => $fields['title']['stringValue'],
                    'message' => $fields['message']['stringValue'],
                    'image_url' => isset($fields['image_url']) ? $fields['image_url']['stringValue'] : 'https://example.com/images/default.jpg',
                    'type' => isset($fields['type']) ? $fields['type']['stringValue'] : 'general',
                    'anime_id' => isset($fields['anime_id']) ? $fields['anime_id']['stringValue'] : null,
                    'timestamp' => isset($fields['created_at']) ? $fields['created_at']['stringValue'] : date('Y-m-d\TH:i:s\Z'),
                    'read' => false,
                    'priority' => isset($fields['priority']) ? $fields['priority']['stringValue'] : 'normal',
                    'action' => [
                        'open_page' => isset($fields['open_page']) ? $fields['open_page']['stringValue'] : 'home',
                        'params' => [
                            'anime_id' => isset($fields['anime_id']) ? $fields['anime_id']['stringValue'] : null
                        ]
                    ]
                ];
                
                $notifications[] = $notification;
            }
        }
        
        // Sort by ID descending (newest first)
        usort($notifications, function($a, $b) {
            return (int)$b['id'] - (int)$a['id'];
        });
        
        echo json_encode([
            'success' => true,
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching notifications: ' . $e->getMessage()
        ]);
    }
}

function pushNotification() {
    global $firebaseConfig;
    
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'No JSON data provided'
        ]);
        return;
    }
    
    // Validate required fields
    if (!isset($data['title']) || !isset($data['message'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: title, message'
        ]);
        return;
    }
    
    try {
        $projectId = $firebaseConfig['projectId'];
        
        // Get current notification counter
        $counterUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/counter";
        $counterResponse = @file_get_contents($counterUrl);
        
        $currentId = 1;
        if ($counterResponse !== false) {
            $counterData = json_decode($counterResponse, true);
            if (isset($counterData['fields']['count']['integerValue'])) {
                $currentId = (int)$counterData['fields']['count']['integerValue'] + 1;
            }
        }
        
        // Create notification document
        $notificationData = [
            'fields' => [
                'id' => ['integerValue' => $currentId],
                'title' => ['stringValue' => $data['title']],
                'message' => ['stringValue' => $data['message']],
                'image_url' => ['stringValue' => isset($data['image_url']) ? $data['image_url'] : 'https://example.com/images/default.jpg'],
                'type' => ['stringValue' => isset($data['type']) ? $data['type'] : 'general'],
                'anime_id' => ['stringValue' => isset($data['anime_id']) ? $data['anime_id'] : ''],
                'priority' => ['stringValue' => isset($data['priority']) ? $data['priority'] : 'normal'],
                'open_page' => ['stringValue' => isset($data['open_page']) ? $data['open_page'] : 'home'],
                'created_at' => ['stringValue' => date('Y-m-d\TH:i:s\Z')],
                'status' => ['stringValue' => 'active']
            ]
        ];
        
        // Save notification
        $notificationUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/push_{$currentId}";
        $context = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($notificationData)
            ]
        ]);
        
        $response = file_get_contents($notificationUrl, false, $context);
        
        if ($response === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save notification'
            ]);
            return;
        }
        
        // Update counter
        $counterData = [
            'fields' => [
                'count' => ['integerValue' => $currentId],
                'updated_at' => ['stringValue' => date('Y-m-d H:i:s')]
            ]
        ];
        
        $counterContext = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($counterData)
            ]
        ]);
        
        file_get_contents($counterUrl, false, $counterContext);
        
        echo json_encode([
            'success' => true,
            'message' => 'Push notification created successfully',
            'data' => [
                'id' => $currentId,
                'title' => $data['title'],
                'message' => $data['message'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating push notification: ' . $e->getMessage()
        ]);
    }
}

?>
