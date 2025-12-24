<?php
require_once __DIR__ . '/config.php';

class AI {
    private $keys;
    
    public function __construct() {
        Config::load();
        $this->keys = [
            Config::get('MISTRAL_API_KEY_1'),
            Config::get('MISTRAL_API_KEY_2'),
            Config::get('MISTRAL_API_KEY_3')
        ];
        $this->keys = array_filter($this->keys);
    }
    
    public function chat($message, $context = '') {
        $prompt = $this->buildPrompt($message, $context);
        
        // Try each key until one works
        foreach($this->keys as $key) {
            $response = $this->makeRequest($prompt, $key);
            if($response !== null) {
                return $response;
            }
        }
        
        return 'Yaar sab keys busy hain, thoda wait karo!';
    }
    
    private function makeRequest($prompt, $key) {
        $data = [
            'model' => 'mistral-tiny',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if(isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            }
        }
        
        // Check for rate limit (429) or other errors
        if($httpCode === 429) {
            file_put_contents('/tmp/hiotaku_debug.log', date('Y-m-d H:i:s') . " - Rate limit hit for key, trying next...\n", FILE_APPEND);
        }
        
        return null; // This key failed, try next
    }
    
    private function buildPrompt($message, $context) {
        $msg = strtolower($message);
        
        if(strpos($msg, 'name') !== false || strpos($msg, 'who are you') !== false) {
            return 'User asked identity. Say you are Hisu from Hiotaku LLMs Model v1 in GenZ style with minimal emojis.';
        }
        
        // Detect language mix
        $hasHindi = preg_match('/\b(hai|me|ka|ke|ki|ko|se|bhai|yaar|kya|koi|dekh|suggest|kar|kro|chal|rha|kesa|kaise)\b/', $msg);
        $langInstruction = $hasHindi ? 'Reply in simple Hinglish - use basic Hindi words like "hai", "me", "bhai", "yaar" mixed with English. Keep it natural and simple like young Indians chat.' : 'Use English GenZ slang.';
        
        return "You are Hisu from Hiotaku LLMs Model v1. $langInstruction Be cool and friendly. Short responses. Use emojis only when they add value or express emotion naturally. Respond to: $message. Context: $context";
    }
}
?>
