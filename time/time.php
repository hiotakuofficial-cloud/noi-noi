<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$zone = $_GET['zone'] ?? 'UTC';

if ($action !== 'time') {
    echo json_encode(['error' => 'Invalid action. Use: time'], JSON_PRETTY_PRINT);
    exit;
}

try {
    $timezone = new DateTimeZone($zone);
    $datetime = new DateTime('now', $timezone);
    
    echo json_encode([
        'success' => true,
        'timezone' => $zone,
        'time' => $datetime->format('H:i:s'),
        'date' => $datetime->format('Y-m-d'),
        'year' => $datetime->format('Y'),
        'month' => $datetime->format('m'),
        'day' => $datetime->format('d'),
        'full_datetime' => $datetime->format('Y-m-d H:i:s'),
        'timestamp' => $datetime->getTimestamp()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Invalid timezone'], JSON_PRETTY_PRINT);
}
?>
