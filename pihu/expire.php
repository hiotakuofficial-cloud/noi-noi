<?php
session_start();
require_once __DIR__ . '/config/check_auth.php';
require_once __DIR__ . '/config/supabase.php';

function supa($method, $path, $data = null) {
    $headers = ["apikey: " . SUPABASE_ANON_KEY, "Authorization: Bearer " . SUPABASE_ANON_KEY, "Content-Type: application/json", "Prefer: return=minimal"];
    $opts = ['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = json_encode($data);
    return json_decode(@file_get_contents(SUPABASE_URL . '/rest/v1/' . $path, false, stream_context_create($opts)), true);
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = trim($_POST['service_account'] ?? '');
    $decoded = json_decode($json, true);
    if (!$decoded || !isset($decoded['private_key'], $decoded['client_email'])) {
        $msg = 'Invalid JSON — must be a valid Firebase service account.';
        $msgType = 'error';
    } else {
        $existing = supa('GET', 'configuration_fcm?select=id&limit=1');
        if (!empty($existing)) {
            $id = $existing[0]['id'];
            supa('PATCH', "configuration_fcm?id=eq.$id", ['service_account' => $decoded, 'updated_at' => date('c')]);
        } else {
            supa('POST', 'configuration_fcm', ['service_account' => $decoded, 'updated_at' => date('c')]);
        }
        $msg = 'Service account updated successfully!';
        $msgType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FCM Config - Hiotaku</title>
<link rel="icon" href="assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Space Grotesk', sans-serif; background: #050505; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 40px; width: 100%; max-width: 600px; margin: 40px; }
h1 { font-size: 1.8rem; margin-bottom: 8px; }
p.sub { color: #666; font-size: 0.9rem; margin-bottom: 32px; }
label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px; }
textarea { width: 100%; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: monospace; font-size: 0.8rem; min-height: 220px; resize: vertical; transition: border 0.3s; }
textarea:focus { outline: none; border-color: rgba(255,255,255,0.3); }
.btn { margin-top: 16px; width: 100%; padding: 14px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 0.1em; }
.btn:hover { opacity: 0.9; }
.alert { padding: 14px; border-radius: 8px; margin-top: 16px; font-size: 0.9rem; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
.back { display: inline-flex; align-items: center; gap: 6px; color: #666; text-decoration: none; font-size: 0.85rem; margin-bottom: 24px; }
.back:hover { color: #fff; }
</style>
</head>
<body>
<div class="card">
  <a href="dashboard/index.php" class="back">← Back to Dashboard</a>
  <h1>FCM Service Account</h1>
  <p class="sub">Paste new Firebase service account JSON when the current one expires.</p>
  <form method="POST">
    <label>Service Account JSON</label>
    <textarea name="service_account" placeholder='{"type":"service_account","project_id":"hiotaku-flutter",...}' required></textarea>
    <button type="submit" class="btn">Update Service Account</button>
    <?php if ($msg): ?>
      <div class="alert <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
