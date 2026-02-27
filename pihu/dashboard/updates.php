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
<title>App Updates - Hiotaku</title>
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
.form-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; }
.form-group { margin-bottom: 20px; }
label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px; font-weight: 500; }
input, textarea, select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 0.95rem; transition: all 0.3s; }
input:focus, textarea:focus, select:focus { outline: none; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
textarea { resize: vertical; min-height: 120px; }
.btn { padding: 14px 32px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.1em; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,255,255,0.2); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.alert { padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 0.9rem; display: none; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
.history-item { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.history-item:last-child { border-bottom: none; }
.history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.version-tag { font-size: 1.2rem; font-weight: 700; }
.update-name { color: #888; font-size: 0.9rem; margin-left: 12px; }
.history-date { color: #666; font-size: 0.8rem; }
.history-desc { color: #aaa; font-size: 0.9rem; line-height: 1.6; margin-top: 8px; }
.history-link { color: #2de08b; font-size: 0.85rem; text-decoration: none; margin-top: 8px; display: inline-block; margin-right: 16px; }
.history-link:hover { text-decoration: underline; }
.btn-delete { color: #ff4d6a; font-size: 0.85rem; cursor: pointer; background: none; border: none; padding: 0; }
.btn-delete:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>App Updates</h1>
  <p class="subtitle">Push new app versions to users</p>

  <div class="form-card">
    <form id="updateForm">
      <div class="form-group">
        <label>Version</label>
        <input type="text" id="version" placeholder="e.g. 2.1.0" required>
      </div>

      <div class="form-group">
        <label>Update Name (Optional)</label>
        <input type="text" id="updateName" placeholder="e.g. Coconut 🥥">
      </div>

      <div class="form-group">
        <label>Download URL</label>
        <input type="url" id="link" placeholder="https://..." required>
      </div>

      <div class="form-group">
        <label>Release Notes</label>
        <textarea id="description" placeholder="What's new in this version..." required></textarea>
      </div>

      <div class="form-group">
        <label>Update Type</label>
        <select id="updateType">
          <option value="optional">Optional</option>
          <option value="recommended">Recommended</option>
          <option value="forced">Forced (sends notification)</option>
        </select>
      </div>

      <button type="submit" class="btn" id="submitBtn">Push Update</button>
      <div id="result" class="alert"></div>
    </form>
  </div>

  <h2 style="font-size: 2rem; margin: 60px 0 20px;">Update History</h2>
  <div class="form-card" id="historyList">
    <p style="color: #666; text-align: center; padding: 20px;">Loading...</p>
  </div>
</div>

<script>
async function loadHistory() {
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/updates?select=*&order=created_at.desc', {
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    const updates = await res.json();
    
    const list = document.getElementById('historyList');
    if (updates.length === 0) {
      list.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No updates yet</p>';
      return;
    }
    
    list.innerHTML = updates.map(u => `
      <div class="history-item">
        <div class="history-header">
          <div>
            <span class="version-tag">v${u.version}</span>
            ${u.update_name ? `<span class="update-name">${u.update_name}</span>` : ''}
          </div>
          <span class="history-date">${new Date(u.created_at).toLocaleDateString()}</span>
        </div>
        <p class="history-desc">${u.description || 'No description'}</p>
        <a href="${u.link}" target="_blank" class="history-link"><i class="fas fa-download"></i> Download</a>
        <button class="btn-delete" onclick="deleteUpdate('${u.version}')"><i class="fas fa-trash"></i> Delete</button>
      </div>
    `).join('');
  } catch (err) {
    document.getElementById('historyList').innerHTML = '<p style="color: #ff4d6a; text-align: center; padding: 20px;">Failed to load history</p>';
  }
}

document.getElementById('updateForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const result = document.getElementById('result');
  
  btn.disabled = true;
  btn.textContent = 'Pushing...';
  
  const data = {
    version: document.getElementById('version').value,
    update_name: document.getElementById('updateName').value,
    link: document.getElementById('link').value,
    description: document.getElementById('description').value,
    update_type: document.getElementById('updateType').value
  };
  
  try {
    const res = await fetch('../api/update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    
    result.className = 'alert ' + (json.success ? 'success' : 'error');
    const notifMsg = json.notification_sent ? ' Notification sent to all users!' : '';
    result.textContent = json.success ? '✓ Update pushed!' + notifMsg : '✗ Error: ' + (json.error || 'Unknown error');
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
  btn.textContent = 'Push Update';
  setTimeout(() => { result.style.display = 'none'; }, 5000);
});

async function deleteUpdate(version) {
  if (!confirm(`Delete version ${version}?`)) return;
  
  try {
    await fetch('<?= SUPABASE_URL ?>/rest/v1/updates?version=eq.' + version, {
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

loadHistory();
</script>
</body>
</html>
