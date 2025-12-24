<?php
class Config {
    public static function load() {
        $env = file_get_contents(__DIR__ . '/../.env');
        foreach(explode("\n", $env) as $line) {
            if(strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    public static function get($key) {
        return $_ENV[$key] ?? null;
    }
}
?>
