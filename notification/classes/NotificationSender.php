<?php
/**
 * Notification Sender Class
 * Handles sending notifications to users via FCM
 */

require_once __DIR__ . '/../config/config.php';

class NotificationSender {
    private $accessToken;
    private $tokenExpiry;
    
    public function __construct() {
        $this->accessToken = null;
        $this->tokenExpiry = 0;
    }
    
    /**
     * Send notification to specific user
     */
    public function sendToUser($userId, $notification) {
        try {
            // Get user's FCM tokens
            $tokens = $this->getUserFCMTokens($userId);
            
            if (empty($tokens)) {
                return [
                    'success' => false,
                    'error' => 'No active FCM tokens found for user'
                ];
            }
            
            // Store notification in database (non-blocking)
            try {
                $this->storeNotification($userId, $notification);
            } catch (Exception $e) {
                error_log("Failed to store notification: " . $e->getMessage());
            }
            
            // Send to each token
            $results = [];
            $successCount = 0;
            
            foreach ($tokens as $token) {
                $result = $this->sendToToken($token, $notification);
                $results[] = $result;
                if ($result['success']) {
                    $successCount++;
                }
            }
            
            return [
                'success' => $successCount > 0,
                'recipients_count' => 1,
                'tokens_sent' => $successCount,
                'total_tokens' => count($tokens),
                'details' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to all users
     */
    public function sendToAllUsers($notification) {
        try {
            // Get all active users with FCM tokens
            $users = $this->getAllUsersWithTokens();
            
            if (empty($users)) {
                return [
                    'success' => false,
                    'error' => 'No users with active FCM tokens found'
                ];
            }
            
            $totalSent = 0;
            $totalUsers = 0;
            $results = [];
            
            foreach ($users as $user) {
                $result = $this->sendToUser($user['user_id'], $notification);
                $results[] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'] ?? 'Unknown',
                    'success' => $result['success'],
                    'tokens_sent' => $result['tokens_sent'] ?? 0
                ];
                
                if ($result['success']) {
                    $totalSent++;
                }
                $totalUsers++;
            }
            
            return [
                'success' => $totalSent > 0,
                'recipients_count' => $totalSent,
                'total_users' => $totalUsers,
                'details' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to specific FCM token
     */
    private function sendToToken($token, $notification) {
        try {
            $accessToken = $this->getAccessToken();
            
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notification['title'],
                        'body' => $notification['body']
                    ],
                    'data' => $this->prepareData($notification['data'] ?? []),
                    'android' => [
                        'notification' => [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'channel_id' => HIGH_IMPORTANCE_CHANNEL,
                            'color' => DEFAULT_NOTIFICATION_COLOR,
                            'icon' => DEFAULT_NOTIFICATION_ICON
                        ]
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'category' => 'HIOTAKU_NOTIFICATION',
                                'sound' => 'default'
                            ]
                        ]
                    ]
                ]
            ];
            
            $response = $this->makeFirebaseRequest($message, $accessToken);
            
            return [
                'success' => isset($response['name']),
                'message_id' => $response['name'] ?? null,
                'token' => substr($token, 0, 20) . '...'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ];
        }
    }
    
    /**
     * Get OAuth 2.0 access token
     */
    private function getAccessToken() {
        // Check if token is still valid
        if ($this->accessToken && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }
        
        // Load service account from env or file
        if (FIREBASE_SERVICE_ACCOUNT_JSON) {
            $serviceAccount = json_decode(FIREBASE_SERVICE_ACCOUNT_JSON, true);
        } else if (FIREBASE_SERVICE_ACCOUNT_PATH && file_exists(FIREBASE_SERVICE_ACCOUNT_PATH)) {
            $serviceAccount = json_decode(file_get_contents(FIREBASE_SERVICE_ACCOUNT_PATH), true);
        } else {
            throw new Exception('Firebase service account not configured');
        }
        
        if (!$serviceAccount) {
            throw new Exception('Invalid service account file');
        }
        
        // Fix private key format - replace escaped newlines with actual newlines
        $privateKey = str_replace('\n', "\n", $serviceAccount['private_key']);
        
        // Create JWT
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => FIREBASE_SCOPE,
            'aud' => OAUTH_ENDPOINT,
            'exp' => $now + 3600,
            'iat' => $now
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        $data = $headerEncoded . '.' . $payloadEncoded;
        
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        $jwt = $data . '.' . $signatureEncoded;
        
        // Request access token
        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents(OAUTH_ENDPOINT, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to get access token');
        }
        
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            throw new Exception('Invalid token response: ' . ($tokenData['error_description'] ?? $tokenData['error'] ?? 'Unknown error'));
        }
        
        $this->accessToken = $tokenData['access_token'];
        $this->tokenExpiry = time() + ($tokenData['expires_in'] ?? 3600) - 60;
        
        return $this->accessToken;
    }
    
    /**
     * Make request to Firebase FCM API
     */
    private function makeFirebaseRequest($message, $accessToken) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                'content' => json_encode($message),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents(FCM_ENDPOINT, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to send FCM request');
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            throw new Exception('Invalid FCM response');
        }
        
        if (isset($decoded['error'])) {
            throw new Exception('FCM Error: ' . $decoded['error']['message']);
        }
        
        return $decoded;
    }
    
    /**
     * Get FCM tokens for a user
     */
    private function getUserFCMTokens($userId) {
        $url = SUPABASE_URL . '/rest/v1/fcm_tokens?user_id=eq.' . urlencode($userId) . '&is_active=eq.true&select=fcm_token';
        
        $response = $this->makeSupabaseRequest('GET', $url);
        
        return array_column($response, 'fcm_token');
    }
    
    /**
     * Get all users with active FCM tokens
     */
    private function getAllUsersWithTokens() {
        $url = SUPABASE_URL . '/rest/v1/fcm_tokens?is_active=eq.true&select=user_id,users(username)';
        
        $response = $this->makeSupabaseRequest('GET', $url);
        
        $users = [];
        foreach ($response as $token) {
            $userId = $token['user_id'];
            if (!isset($users[$userId])) {
                $users[$userId] = [
                    'user_id' => $userId,
                    'username' => $token['users']['username'] ?? 'Unknown'
                ];
            }
        }
        
        return array_values($users);
    }
    
    /**
     * Store notification in database
     */
    private function storeNotification($userId, $notification) {
        $url = SUPABASE_URL . '/rest/v1/notifications';
        
        $data = [
            'user_id' => $userId,
            'title' => $notification['title'],
            'body' => $notification['body'],
            'type' => $notification['type'] ?? 'general',
            'data' => json_encode($notification['data'] ?? []),
            'is_sent' => true,
            'sent_at' => date('c')
        ];
        
        return $this->makeSupabaseRequest('POST', $url, $data);
    }
    
    /**
     * Make request to Supabase
     */
    private function makeSupabaseRequest($method, $url, $data = null) {
        $headers = [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json'
        ];
        
        $context = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true
            ]
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $context['http']['content'] = json_encode($data);
        }
        
        $response = file_get_contents($url, false, stream_context_create($context));
        
        if ($response === false) {
            throw new Exception("Supabase request failed: $method $url");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Prepare data for FCM (all values must be strings)
     */
    private function prepareData($data) {
        // If empty array, return empty object for FCM
        if (empty($data)) {
            return new stdClass();
        }
        
        $prepared = [];
        foreach ($data as $key => $value) {
            $prepared[$key] = is_string($value) ? $value : json_encode($value);
        }
        return $prepared;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
?>
