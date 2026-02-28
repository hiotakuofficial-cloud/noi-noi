<?php
require_once __DIR__ . '/notification/config/config.php';

echo "Config Check:\n";
echo "Path defined: " . (FIREBASE_SERVICE_ACCOUNT_PATH ? "YES" : "NO") . "\n";
echo "JSON defined: " . (FIREBASE_SERVICE_ACCOUNT_JSON ? "YES" : "NO") . "\n";

if (FIREBASE_SERVICE_ACCOUNT_PATH) {
    echo "File exists: " . (file_exists(FIREBASE_SERVICE_ACCOUNT_PATH) ? "YES" : "NO") . "\n";
    if (file_exists(FIREBASE_SERVICE_ACCOUNT_PATH)) {
        $sa = json_decode(file_get_contents(FIREBASE_SERVICE_ACCOUNT_PATH), true);
        echo "Key ID: " . $sa["private_key_id"] . "\n";
    }
}
