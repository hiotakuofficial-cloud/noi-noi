<?php
require_once __DIR__ . '/config.php';

class Auth {
    private $validTokens;
    
    public function __construct() {
        Config::load();
        $tokens = Config::get('API_TOKENS');
        $this->validTokens = explode(',', $tokens);
    }
    
    public function validateToken($token) {
        return in_array(trim($token), $this->validTokens);
    }
    
    public function authenticate() {
        // Check Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if(strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            return $this->validateToken($token);
        }
        
        // Check query parameter
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        if(!empty($token)) {
            return $this->validateToken($token);
        }
        
        return false;
    }
    
    public function requireAuth() {
        if(!$this->authenticate()) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'Valid token required. Use Authorization: Bearer <token> header or ?token=<token> parameter'
            ]);
            exit;
        }
    }
}
?>
