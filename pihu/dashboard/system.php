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
<title>System Settings - Hiotaku</title>
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
.settings-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; margin-bottom: 24px; }
.setting-item { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.setting-item:last-child { border-bottom: none; }
.setting-info h3 { font-size: 1.2rem; margin-bottom: 8px; font-weight: 600; }
.setting-info p { color: #888; font-size: 0.9rem; line-height: 1.5; }
.toggle { position: relative; width: 60px; height: 32px; background: rgba(255,255,255,0.1); border-radius: 16px; cursor: pointer; transition: all 0.3s; }
.toggle.active { background: #2de08b; }
.toggle-slider { position: absolute; top: 4px; left: 4px; width: 24px; height: 24px; background: #fff; border-radius: 50%; transition: all 0.3s; }
.toggle.active .toggle-slider { left: 32px; }
.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem; display: none; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
</style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>System Settings</h1>
  <p class="subtitle">Configure app features and services</p>

  <div id="alert" class="alert"></div>

  <div class="settings-card">
    <h2 style="font-size: 1.5rem; margin-bottom: 24px;">App Features</h2>
    
    <div class="setting-item">
      <div class="setting-info">
        <h3><i class="fas fa-comments"></i> AI Chat</h3>
        <p>Enable or disable AI chat feature for all users</p>
      </div>
      <div class="toggle" id="chatToggle" onclick="toggleSetting('is_chat_enabled')">
        <div class="toggle-slider"></div>
      </div>
    </div>

    <div class="setting-item">
      <div class="setting-info">
        <h3><i class="fas fa-download"></i> Downloads</h3>
        <p>Allow users to download anime episodes</p>
      </div>
      <div class="toggle" id="downloadToggle" onclick="toggleSetting('is_download_enabled')">
        <div class="toggle-slider"></div>
      </div>
    </div>
  </div>
</div>

<script>
let settings = {};

async function loadSettings() {
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/system_settings?select=*&limit=1', {
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    const data = await res.json();
    
    if (data.length > 0) {
      settings = data[0];
      updateUI();
    }
  } catch (err) {
    showAlert('Failed to load settings', 'error');
  }
}

function updateUI() {
  document.getElementById('chatToggle').classList.toggle('active', settings.is_chat_enabled);
  document.getElementById('downloadToggle').classList.toggle('active', settings.is_download_enabled);
}

async function toggleSetting(field) {
  const newValue = !settings[field];
  
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/system_settings?id=eq.' + settings.id, {
      method: 'PATCH',
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>',
        'Content-Type': 'application/json',
        'Prefer': 'return=minimal'
      },
      body: JSON.stringify({ [field]: newValue, updated_at: new Date().toISOString() })
    });
    
    if (res.ok) {
      settings[field] = newValue;
      updateUI();
      showAlert('Setting updated successfully', 'success');
    } else {
      showAlert('Failed to update setting', 'error');
    }
  } catch (err) {
    showAlert('Network error', 'error');
  }
}

function showAlert(message, type) {
  const alert = document.getElementById('alert');
  alert.className = 'alert ' + type;
  alert.textContent = message;
  alert.style.display = 'block';
  setTimeout(() => { alert.style.display = 'none'; }, 3000);
}

loadSettings();
</script>
</body>
</html>
