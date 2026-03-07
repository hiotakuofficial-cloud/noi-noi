<?php
/**
 * Simple file-based cache for MovieBox API
 * Reduces API calls and improves response time
 */

class Cache {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct($cacheDir = __DIR__) {
        $this->cacheDir = rtrim($cacheDir, '/');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check if expired
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cache data
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($file, json_encode($data));
        return true;
    }
    
    /**
     * Delete cache entry
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Clean expired cache
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data['expires'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache stats
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $total = count($files);
        $expired = 0;
        $size = 0;
        
        foreach ($files as $file) {
            $size += filesize($file);
            $data = json_decode(file_get_contents($file), true);
            if ($data['expires'] < time()) {
                $expired++;
            }
        }
        
        return [
            'total' => $total,
            'active' => $total - $expired,
            'expired' => $expired,
            'size' => $size,
            'size_mb' => round($size / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Generate cache key
     */
    public static function key($action, $params = []) {
        ksort($params);
        return md5($action . '_' . json_encode($params));
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cacheDir . '/' . $key . '.cache';
    }
}
