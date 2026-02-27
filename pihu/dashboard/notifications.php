<?php
session_start();
require_once __DIR__ . '/../config/check_auth.php';
require_once __DIR__ . '/../config/supabase.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - Hiotaku</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Space Grotesk', sans-serif; background: #050505; color: #fff; min-height: 100vh; }
.container { max-width: 1000px; margin: 0 auto; padding: 40px; }
.back { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; text-decoration: none; font-size: 0.85rem; margin-bottom: 40px; transition: all 0.3s; }
.back:hover { background: rgba(255,255,255,0.1); transform: translateX(-4px); }
h1 { font-size: 3rem; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.03em; }
.subtitle { color: #666; font-size: 1rem; margin-bottom: 40px; }
.form-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; margin-bottom: 24px; }
.form-group { margin-bottom: 20px; }
label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px; font-weight: 500; }
input, textarea, select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 0.95rem; transition: all 0.3s; }
input:focus, textarea:focus, select:focus { outline: none; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
textarea { resize: vertical; min-height: 100px; }
.btn { padding: 14px 32px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.1em; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,255,255,0.2); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.alert { padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 0.9rem; display: none; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
.history-item { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.history-item:last-child { border-bottom: none; }
.history-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.notif-title { font-size: 1.1rem; font-weight: 600; }
.notif-date { color: #666; font-size: 0.8rem; }
.notif-body { color: #aaa; font-size: 0.9rem; line-height: 1.6; margin-top: 8px; }
.notif-meta { color: #888; font-size: 0.75rem; margin-top: 8px; text-transform: uppercase; letter-spacing: 0.1em; }
.btn-delete { color: #ff4d6a; font-size: 0.85rem; cursor: pointer; background: none; border: none; padding: 0; margin-left: 16px; }
.btn-delete:hover { text-decoration: underline; }
.clear-all { display: inline-block; padding: 8px 16px; background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; border-radius: 8px; font-size: 0.85rem; cursor: pointer; margin-bottom: 20px; }
.clear-all:hover { background: rgba(255,77,106,0.2); }
</style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>Notifications</h1>
  <p class="subtitle">Send push notifications to users</p>

  <div class="form-card">
    <form id="notifForm">
      <div class="form-group">
        <label>Send To</label>
        <select id="sendType">
          <option value="all">All Users</option>
          <option value="specific">Specific User</option>
        </select>
      </div>

      <div class="form-group" id="userIdField" style="display:none">
        <label>User ID</label>
        <input type="text" id="userId" placeholder="Enter user UUID">
      </div>

      <div class="form-group">
        <label>Title</label>
        <input type="text" id="title" placeholder="Notification title" required>
      </div>

      <div class="form-group">
        <label>Message</label>
        <textarea id="body" placeholder="Notification message" required></textarea>
      </div>

      <div class="form-group">
        <label>Type</label>
        <select id="type">
          <option value="general">General</option>
          <option value="update">Update</option>
          <option value="alert">Alert</option>
        </select>
      </div>

      <button type="submit" class="btn" id="submitBtn">Send Notification</button>
      <div id="result" class="alert"></div>
    </form>
  </div>

  <h2 style="font-size: 2rem; margin: 60px 0 20px;">Notification History</h2>
  <button class="clear-all" onclick="clearAllNotifications()"><i class="fas fa-trash"></i> Clear All History</button>
  <div class="form-card" id="historyList">
    <p style="color: #666; text-align: center; padding: 20px;">Loading...</p>
  </div>
</div>

<script>
async function loadHistory() {
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/notifications?select=*&order=created_at.desc&limit=50', {
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    const notifs = await res.json();
    
    const list = document.getElementById('historyList');
    if (notifs.length === 0) {
      list.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No notifications sent yet</p>';
      return;
    }
    
    list.innerHTML = notifs.map(n => `
      <div class="history-item">
        <div class="history-header">
          <div class="notif-title">${n.title || 'No title'}</div>
          <div>
            <span class="notif-date">${new Date(n.created_at).toLocaleString()}</span>
            <button class="btn-delete" onclick="deleteNotification('${n.id}')"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <div class="notif-body">${n.body || 'No message'}</div>
        <div class="notif-meta">Type: ${n.type || 'general'} • User: ${n.user_id || 'All users'}</div>
      </div>
    `).join('');
  } catch (err) {
    document.getElementById('historyList').innerHTML = '<p style="color: #ff4d6a; text-align: center; padding: 20px;">Failed to load history</p>';
  }
}

document.getElementById('sendType').addEventListener('change', function() {
  document.getElementById('userIdField').style.display = this.value === 'specific' ? 'block' : 'none';
});

document.getElementById('notifForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const result = document.getElementById('result');
  
  btn.disabled = true;
  btn.textContent = 'Sending...';
  
  const data = {
    send_type: document.getElementById('sendType').value,
    user_id: document.getElementById('userId').value || null,
    title: document.getElementById('title').value,
    body: document.getElementById('body').value,
    type: document.getElementById('type').value,
    screen: '/main',
    click_action: 'FLUTTER_NOTIFICATION_CLICK'
  };
  
  try {
    const res = await fetch('../../notification/api/dashboard_send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    
    result.className = 'alert ' + (json.success ? 'success' : 'error');
    result.textContent = json.success ? '✓ Notification sent successfully!' : '✗ Error: ' + (json.error || 'Unknown error');
    result.style.display = 'block';
    
    if (json.success) {
      e.target.reset();
      loadHistory();
    }
  } catch (err) {
    result.className = 'alert error';
    result.textContent = '✗ Network error';
    result.style.display = 'block';
  }
  
  btn.disabled = false;
  btn.textContent = 'Send Notification';
  setTimeout(() => { result.style.display = 'none'; }, 5000);
});

async function deleteNotification(id) {
  if (!confirm('Delete this notification?')) return;
  
  try {
    await fetch('<?= SUPABASE_URL ?>/rest/v1/notifications?id=eq.' + id, {
      method: 'DELETE',
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    loadHistory();
  } catch (err) {
    alert('Failed to delete');
  }
}

async function clearAllNotifications() {
  if (!confirm('Delete ALL notification history? This cannot be undone!')) return;
  
  try {
    await fetch('<?= SUPABASE_URL ?>/rest/v1/notifications?id=neq.00000000-0000-0000-0000-000000000000', {
      method: 'DELETE',
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    loadHistory();
  } catch (err) {
    alert('Failed to clear history');
  }
}

loadHistory();
</script>
</body>
</html>
