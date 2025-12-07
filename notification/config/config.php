<?php
/**
 * Configuration for Hiotaku Notification Center
 */

// Supabase Configuration
define('SUPABASE_URL', 'https://brwzqawoncblbxqoqyua.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMzM1MjIsImV4cCI6MjA3NzkwOTUyMn0.-HNrfcz5K2N6f_Q8tQsWtsUJCV_SW13Hcj565qU5eCA');

// Firebase Configuration
define('FIREBASE_PROJECT_ID', 'hiotaku-flutter');
define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/../../service-account.json');
define('FCM_ENDPOINT', 'https://fcm.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/messages:send');
define('OAUTH_ENDPOINT', 'https://oauth2.googleapis.com/token');
define('FIREBASE_SCOPE', 'https://www.googleapis.com/auth/firebase.messaging');

// Notification Settings
define('DEFAULT_NOTIFICATION_ICON', '/icon-192x192.png');
define('DEFAULT_NOTIFICATION_COLOR', '#FF8C00');
define('HIGH_IMPORTANCE_CHANNEL', 'high_importance_channel');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('UTC');
?>
