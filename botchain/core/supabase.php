<?php
require_once __DIR__ . '/config.php';

class SupabaseDB {
    private $url;
    private $key;
    
    public function __construct() {
        Config::load();
        $this->url = Config::get('SUPABASE_URL');
        $this->key = Config::get('SUPABASE_KEY');
    }
    
    public function makeRequest($endpoint, $method = 'GET', $data = null) {
        $ch = curl_init($this->url . '/rest/v1/' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
        
        if($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    public function getChatHistory($userId, $limit = 30) {
        $endpoint = "chat_history?user_id=eq.$userId&order=created_at.desc&limit=$limit";
        $result = $this->makeRequest($endpoint);
        return $result ? array_reverse($result) : []; // Reverse to get chronological order
    }
    
    public function saveChatMessage($userId, $userMessage, $botResponse) {
        $data = [
            'user_id' => $userId,
            'user_message' => $userMessage,
            'bot_response' => $botResponse
        ];
        
        return $this->makeRequest('chat_history', 'POST', $data);
    }
    
    public function userExists($userUuid) {
        // Try firebase_uid first (most common)
        $endpoint = "users?firebase_uid=eq.$userUuid&select=id";
        $result = $this->makeRequest($endpoint);
        if(!empty($result)) {
            return true;
        }
        
        // Then try by UUID
        $endpoint = "users?id=eq.$userUuid&select=id";
        $result = $this->makeRequest($endpoint);
        return !empty($result);
    }
    
    public function getUserInfo($userUuid) {
        // Try firebase_uid first
        $endpoint = "users?firebase_uid=eq.$userUuid&select=display_name,username";
        $result = $this->makeRequest($endpoint);
        if(!empty($result)) {
            return $result[0];
        }
        
        // Then try by UUID
        $endpoint = "users?id=eq.$userUuid&select=display_name,username";
        $result = $this->makeRequest($endpoint);
        return !empty($result) ? $result[0] : null;
    }
    
    public function updateUserActivity($userId) {
        // Update last_active in chat_users table
        $endpoint = "chat_users?user_id=eq.$userId";
        $data = ['last_active' => date('c')]; // ISO 8601 format
        
        // Try to update first, if no rows affected, insert new user
        $ch = curl_init($this->url . '/rest/v1/' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);
        
        // If no user found, create new one
        if(empty($result)) {
            $newUser = [
                'user_id' => $userId,
                'last_active' => date('c')
            ];
            $this->makeRequest('chat_users', 'POST', $newUser);
        }
    }
}
?>
