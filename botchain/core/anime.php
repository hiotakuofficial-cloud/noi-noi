<?php
require_once __DIR__ . '/config.php';

class AnimeAPI {
    private $url;
    private $key;
    
    public function __construct() {
        Config::load();
        $this->url = Config::get('ANIME_API_URL');
        $this->key = Config::get('ANIME_API_KEY');
    }
    
    public function search($query) {
        $ch = curl_init($this->url . '?action=search&q=' . urlencode($query) . '&token=' . $this->key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function check($name) {
        $ch = curl_init($this->url . '?action=search&q=' . urlencode($name) . '&token=' . $this->key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if($data && isset($data['results']) && !empty($data['results'])) {
            // If we got any results, consider it available
            return [
                'available' => true, 
                'matches' => $data['results'],
                'total' => $data['total']
            ];
        }
        
        return ['available' => false, 'matches' => [], 'total' => 0];
    }
}
?>
