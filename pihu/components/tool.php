<?php
// Internal tool — only accessible with secret
if (($_GET['auth'] ?? '') !== 'makichutrandi' || ($_GET['pass'] ?? '') !== 'pihu7890') {
    header('Location: /waiting/'); exit;
}

header('Content-Type: application/json');

define('SUPABASE_URL', 'https://brwzqawoncblbxqoqyua.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMzM1MjIsImV4cCI6MjA3NzkwOTUyMn0.-HNrfcz5K2N6f_Q8tQsWtsUJCV_SW13Hcj565qU5eCA');

function supa($path) {
    $h = ["apikey: ".SUPABASE_KEY,"Authorization: Bearer ".SUPABASE_KEY];
    $o = ['http'=>['method'=>'GET','header'=>implode("\r\n",$h),'ignore_errors'=>true]];
    return json_decode(@file_get_contents(SUPABASE_URL.'/rest/v1/'.$path,false,stream_context_create($o)),true);
}

$today = date('Y-m-d');
$rows = supa('cron_notifications?scheduled_at=gte.'.$today.'T00:00:00&scheduled_at=lte.'.$today.'T23:59:59&order=scheduled_at.asc&limit=50') ?? [];

echo json_encode([
    'success'      => true,
    'current_time' => date('c'),
    'today'        => $today,
    'total'        => count($rows),
    'notifications'=> array_map(fn($r) => [
        'id'           => $r['id'],
        'title'        => $r['title'],
        'message'      => $r['message'],
        'image_url'    => $r['image_url'],
        'type'         => $r['type'],
        'scheduled_at' => $r['scheduled_at'],
        'status'       => $r['status'],
        'sent_at'      => $r['sent_at'],
    ], $rows)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
