<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Launch date: 17 Feb 2026, 00:00 IST
$launchDate = strtotime('2026-02-17 00:00:00');
$currentDate = time();

// Check if current date is 17 Feb 2026 or later
if ($currentDate >= $launchDate) {
    // Redirect to APK download
    header('Location: /hiotaku/relised/hiotaku.apk');
    exit();
} else {
    // Redirect to waiting page
    header('Location: /waiting/');
    exit();
}
?>
