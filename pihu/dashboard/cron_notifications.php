<?php
// Auth
if (($_GET['auth'] ?? '') !== 'makichutrandi' || ($_GET['pass'] ?? '') !== 'pihu7890') {
    header('Location: /waiting/'); exit;
}

header('Content-Type: application/json');

define('SUPABASE_URL', 'https://brwzqawoncblbxqoqyua.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJyd3pxYXdvbmNibGJ4cW9xeXVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjIzMzM1MjIsImV4cCI6MjA3NzkwOTUyMn0.-HNrfcz5K2N6f_Q8tQsWtsUJCV_SW13Hcj565qU5eCA');
define('API_TOKEN',   'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7');
define('SEARCH_API',  'https://anime-check-api.hiotaku-official.workers.dev/');
define('MOVIEBOX_API','https://www.hiotaku.in/moviebox/api.php?token='.API_TOKEN);
define('SEND_API',    'https://www.hiotaku.in/notification/api/send.php?token='.API_TOKEN);
define('GROQ_KEYS', getenv('HISU_KEYS') ?: '');

function supa($method, $path, $data = null) {
    $h = ["apikey: ".SUPABASE_KEY,"Authorization: Bearer ".SUPABASE_KEY,"Content-Type: application/json","Prefer: return=representation"];
    $o = ['http'=>['method'=>$method,'header'=>implode("\r\n",$h),'ignore_errors'=>true]];
    if ($data) $o['http']['content'] = json_encode($data);
    return json_decode(@file_get_contents(SUPABASE_URL.'/rest/v1/'.$path,false,stream_context_create($o)),true);
}

function callGroq($prompt) {
    $keys = array_map('trim', explode(',', GROQ_KEYS));
    $body = json_encode(['model'=>'llama-3.3-70b-versatile','messages'=>[
        ['role'=>'system','content'=>'You are a hype notification writer for Hiotaku — India\'s anime & entertainment app. Your job: write ONE push notification that makes people instantly want to open the app. Rules: Hinglish (Hindi in Roman script) or English only — NEVER Devanagari. Gen-Z tone — use slang, energy, FOMO. Mix emojis + symbols (→ ✦ •) naturally, max 2 emojis. Mention genre/vibe/rating if available. Title: max 50 chars, punchy. Message: max 100 chars, create urgency or curiosity. Return ONLY valid JSON: {"title":"...","message":"..."}'],
        ['role'=>'user','content'=>$prompt]
    ],'max_tokens'=>150,'temperature'=>0.92]);
    foreach ($keys as $key) {
        if (!$key) continue;
        $o = ['http'=>['method'=>'POST','timeout'=>20,'ignore_errors'=>true,'header'=>"Authorization: Bearer $key\r\nContent-Type: application/json",'content'=>$body]];
        $r = json_decode(@file_get_contents('https://api.groq.com/openai/v1/chat/completions',false,stream_context_create($o)),true);
        if (isset($r['choices'][0]['message']['content'])) return $r['choices'][0]['message']['content'];
    }
    return null;
}

// ── Step 1: Pick random category ──
$categories = ['anime','bollywood','korean-drama','hollywood','trending-cinema'];
$cat = $categories[array_rand($categories)];

$title = ''; $image = ''; $genre = ''; $desc = '';

if ($cat === 'anime') {
    $animeList = ['naruto','demon slayer','one piece','jujutsu kaisen','attack on titan','bleach','dragon ball','my hero academia','death note','fullmetal alchemist','hunter x hunter','fairy tail','black clover','tokyo ghoul','sword art online'];
    $q = $animeList[array_rand($animeList)];
    $o = ['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['password'=>'nehubaby7890','action'=>'check','query'=>$q]),'timeout'=>10,'ignore_errors'=>true]];
    $res = json_decode(@file_get_contents(SEARCH_API,false,stream_context_create($o)),true);
    $hindiItems = $res['hindi']['results'] ?? [];
    $engItems   = $res['english']['results'] ?? [];
    $item = !empty($hindiItems) ? $hindiItems[0] : ($engItems[0] ?? null);
    $srcType = !empty($hindiItems) ? 'hindi' : 'english';
    if ($item) {
        $ch = curl_init(SEARCH_API);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['password'=>'nehubaby7890','action'=>'details','id'=>$item['id'],'type'=>$srcType])]);
        $d = json_decode(curl_exec($ch),true)['data']??[]; curl_close($ch);
        $title = $d['title'] ?? $item['title'];
        $image = $d['thumbnail'] ?? '';
        $genre = implode(', ', (array)($d['genres']??[]));
        $desc  = substr($d['synopsis']??$d['description']??'',0,120);
    }
} else {
    $page = rand(1,4);
    $ch = curl_init(MOVIEBOX_API.'&action=content&type='.$cat.'&page='.$page.'&perPage=10');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
    $res = json_decode(curl_exec($ch),true); curl_close($ch);
    $items = $res['data']['subjectList'] ?? [];
    if (empty($items)) {
        $ch2 = curl_init(MOVIEBOX_API.'&action=content&type='.$cat.'&page=2&perPage=10');
        curl_setopt_array($ch2,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
        $res = json_decode(curl_exec($ch2),true); curl_close($ch2);
        $items = $res['data']['subjectList'] ?? [];
    }
    if (!empty($items)) {
        $item = $items[array_rand($items)];
        $title = $item['title'] ?? '';
        $image = $item['cover']['url'] ?? '';
        $genre = $item['genre'] ?? '';
        $desc  = substr($item['description']??'',0,120);
    }
}

if (empty($title)) {
    echo json_encode(['success'=>false,'error'=>'Could not fetch content']); exit;
}

// ── Step 2: Generate notification via Groq ──
$raw = callGroq("Write 1 push notification for: Title: $title, Category: $cat, Genre: $genre, Description: $desc\nReturn JSON: {\"title\":\"...\",\"message\":\"...\"}");
preg_match('/\{.*\}/s', $raw ?? '', $m);
$notif = json_decode($m[0] ?? '{}', true);

if (empty($notif['title']) || empty($notif['message'])) {
    echo json_encode(['success'=>false,'error'=>'Groq failed','raw'=>$raw]); exit;
}

// ── Step 3: Save to Supabase ──
$now = date('c');
supa('POST','cron_notifications',[
    'title'        => $notif['title'],
    'message'      => $notif['message'],
    'image_url'    => $image,
    'type'         => $cat,
    'scheduled_at' => $now,
    'status'       => 'sending'
]);

// ── Step 4: Send to all users ──
$o = ['http'=>['method'=>'POST','header'=>'Content-Type: application/json','timeout'=>15,'ignore_errors'=>true,
    'content'=>json_encode(['title'=>$notif['title'],'body'=>$notif['message'],'send_type'=>'all','type'=>$cat,'image_url'=>$image,'data'=>['image_url'=>$image]])]];
$result = json_decode(@file_get_contents(SEND_API,false,stream_context_create($o)),true);

// ── Step 5: Update status ──
$sent_to = []; $failed = [];
foreach ($result['details'] ?? [] as $d) {
    if ($d['success']) $sent_to[] = $d['username'];
    else $failed[] = $d['username'];
}

// Mark sent in supabase
$rows = supa('GET','cron_notifications?status=eq.sending&order=created_at.desc&limit=1');
if (!empty($rows)) {
    supa('PATCH','cron_notifications?id=eq.'.$rows[0]['id'],['status'=>'sent','sent_at'=>$now]);
}

echo json_encode([
    'success'      => !empty($result['success']),
    'current_time' => $now,
    'category'     => $cat,
    'notification' => ['title'=>$notif['title'],'message'=>$notif['message'],'image'=>$image],
    'sent_to'      => $sent_to,
    'failed'       => $failed,
    'recipients'   => $result['recipients_count'] ?? 0
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
