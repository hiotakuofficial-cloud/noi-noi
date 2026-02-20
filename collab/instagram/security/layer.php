<?php

class SecurityLayer {
    
    public static function validateInput($input, $type = 'username') {
        if (empty($input)) {
            return false;
        }
        
        switch ($type) {
            case 'username':
                // Instagram username validation
                if (strlen($input) > 30 || strlen($input) < 1) {
                    return false;
                }
                if (!preg_match('/^[a-zA-Z0-9._]+$/', $input)) {
                    return false;
                }
                break;
                
            case 'action':
                // Only allow 'insta' action
                if ($input !== 'insta') {
                    return false;
                }
                break;
                
            case 'key':
                // Key validation
                if (strlen($input) < 10 || strlen($input) > 100) {
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    public static function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    private static $rateCache = [];
    
    public static function checkRateLimit($ip) {
        $currentTime = time();
        $timeWindow = 60; // 1 minute
        $maxRequests = 10; // Max 10 requests per minute
        
        // Use memory cache instead of file
        if (!isset(self::$rateCache[$ip])) {
            self::$rateCache[$ip] = [];
        }
        
        // Remove old entries
        self::$rateCache[$ip] = array_filter(self::$rateCache[$ip], function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count(self::$rateCache[$ip]) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        self::$rateCache[$ip][] = $currentTime;
        
        return true;
    }
    
    public static function validateUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Block common bot user agents
        $blockedAgents = [
            'curl',
            'wget',
            'python',
            'bot',
            'crawler',
            'spider',
            'scraper'
        ];
        
        foreach ($blockedAgents as $blocked) {
            if (stripos($userAgent, $blocked) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    public static function validateReferer() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $allowedDomains = [
            'localhost',
            '127.0.0.1',
            'your-domain.com' // Add your allowed domains
        ];
        
        if (empty($referer)) {
            return false; // No referer = suspicious
        }
        
        $refererHost = parse_url($referer, PHP_URL_HOST);
        
        foreach ($allowedDomains as $domain) {
            if (strpos($refererHost, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function generateSecurityToken() {
        return bin2hex(random_bytes(16));
    }
    
    public static function validateSecurityToken($token, $expectedToken) {
        return hash_equals($expectedToken, $token);
    }
    
    public static function logSecurityEvent($event, $ip, $details = '') {
        // Use error_log instead of file for better compatibility
        error_log("Security Event - IP: {$ip} | Event: {$event} | Details: {$details}");
    }
    
    public static function blockSuspiciousActivity($ip, $reason) {
        $blockedFile = __DIR__ . '/blocked_ips.json';
        
        if (!file_exists($blockedFile)) {
            file_put_contents($blockedFile, json_encode([]));
        }
        
        $blockedIPs = json_decode(file_get_contents($blockedFile), true);
        $blockedIPs[$ip] = [
            'blocked_at' => time(),
            'reason' => $reason
        ];
        
        file_put_contents($blockedFile, json_encode($blockedIPs));
        
        self::logSecurityEvent('IP_BLOCKED', $ip, $reason);
    }
    
    public static function isIPBlocked($ip) {
        $blockedFile = __DIR__ . '/blocked_ips.json';
        
        if (!file_exists($blockedFile)) {
            return false;
        }
        
        $blockedIPs = json_decode(file_get_contents($blockedFile), true);
        
        if (isset($blockedIPs[$ip])) {
            // Check if block is still valid (24 hours)
            if ((time() - $blockedIPs[$ip]['blocked_at']) < 86400) {
                return true;
            } else {
                // Remove expired block
                unset($blockedIPs[$ip]);
                file_put_contents($blockedFile, json_encode($blockedIPs));
            }
        }
        
        return false;
    }
}
?>
