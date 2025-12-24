<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase.php';

class ChatSession {
    private $sessionDir;
    private $supabase;
    
    public function __construct() {
        $this->sessionDir = '/tmp/hiotaku_sessions/';
        if(!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir, 0755, true);
        }
        $this->supabase = new SupabaseDB();
    }
    
    public function getHistory($userId, $limit = 10) {
        // Use file-based storage for now (faster)
        $file = $this->sessionDir . 'user_' . md5($userId) . '.json';
        if(!file_exists($file)) {
            return [];
        }
        
        $history = json_decode(file_get_contents($file), true) ?? [];
        return array_slice($history, -$limit);
    }
    
    public function addMessage($userId, $userMessage, $botResponse) {
        // Skip Supabase calls for speed - only use files
        $file = $this->sessionDir . 'user_' . md5($userId) . '.json';
        $history = $this->getHistory($userId, 30);
        
        $history[] = [
            'user' => $userMessage,
            'bot' => $botResponse,
            'timestamp' => time()
        ];
        
        // Keep only last 30 messages
        $history = array_slice($history, -30);
        file_put_contents($file, json_encode($history));
    }
    
    public function buildContext($userId) {
        $history = $this->getHistory($userId, 5); // Last 5 exchanges for context
        if(empty($history)) {
            return '';
        }
        
        $context = "\nRECENT CONVERSATION HISTORY:\n";
        foreach($history as $exchange) {
            $context .= "User said: \"" . $exchange['user'] . "\"\n";
            $context .= "You (Hisu) replied: \"" . substr($exchange['bot'], 0, 100) . "...\"\n\n";
        }
        $context .= "Now user is asking a new question. Remember this conversation and respond naturally.\n";
        
        return $context;
    }
}
?>
