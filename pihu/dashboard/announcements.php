<?php
session_start();
require_once __DIR__ . '/../config/check_auth.php';
$admin_type = $_SESSION['admin_type'];
require_once __DIR__ . '/../config/supabase.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements - Hiotaku</title>
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
textarea { resize: vertical; min-height: 120px; }
.btn { padding: 14px 32px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.1em; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,255,255,0.2); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-danger { background: #ff4d6a; color: #fff; padding: 8px 16px; font-size: 0.8rem; }
.alert { padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 0.9rem; display: none; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
.list { margin-top: 40px; }
.list-item { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 24px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: start; }
.list-content h3 { font-size: 1.1rem; margin-bottom: 8px; }
.list-content p { font-size: 0.85rem; color: #666; margin-bottom: 12px; }
.list-meta { font-size: 0.75rem; color: #555; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; margin-right: 8px; }
.badge.active { background: rgba(45,224,139,0.1); color: #2de08b; border: 1px solid rgba(45,224,139,0.2); }
.badge.expired { background: rgba(255,77,106,0.1); color: #ff4d6a; border: 1px solid rgba(255,77,106,0.2); }
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
.modal.show { display: flex; }
.modal-content { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.2); border-radius: 16px; padding: 32px; max-width: 400px; width: 90%; }
.modal-content h3 { font-size: 1.5rem; margin-bottom: 16px; }
.modal-content p { color: #888; margin-bottom: 24px; line-height: 1.6; }
.modal-actions { display: flex; gap: 12px; }
.modal-actions .btn { flex: 1; padding: 12px; font-size: 0.85rem; }
.modal-actions .btn.secondary { background: rgba(255,255,255,0.05); color: #fff; }
.modal-actions .btn.danger { background: #ff4d6a; color: #fff; }
</style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>Announcements</h1>
  <p class="subtitle">Create and manage in-app announcements</p>

  <div class="form-card">
    <form id="announceForm">
      <div class="form-group">
        <label>Title</label>
        <input type="text" id="title" placeholder="Announcement title" required>
      </div>

      <div class="form-group">
        <label>Message</label>
        <textarea id="description" placeholder="Announcement message" required></textarea>
      </div>

      <div class="form-group">
        <label>Priority</label>
        <select id="priority">
          <option value="low">Low (7 days)</option>
          <option value="medium">Medium (14 days)</option>
          <option value="high">High (30 days)</option>
          <option value="critical">Critical (90 days)</option>
        </select>
      </div>

      <button type="submit" class="btn" id="submitBtn">Publish Announcement</button>
      <div id="result" class="alert"></div>
    </form>
  </div>

  <div class="list">
    <h2 style="font-size:1.5rem;margin-bottom:20px">All Announcements</h2>
    <div id="announceList">Loading...</div>
  </div>
</div>

<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h3>Delete Announcement</h3>
    <p id="deleteText"></p>
    <div class="modal-actions">
      <button class="btn secondary" onclick="closeDeleteModal()">Cancel</button>
      <button class="btn danger" id="confirmDelete">Delete</button>
    </div>
  </div>
</div>

<script>
document.getElementById('announceForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const result = document.getElementById('result');
  
  btn.disabled = true;
  btn.textContent = 'Publishing...';
  
  const data = {
    title: document.getElementById('title').value,
    description: document.getElementById('description').value,
    priority: document.getElementById('priority').value
  };
  
  try {
    const res = await fetch('../api/announcement.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    
    result.className = 'alert ' + (json.success ? 'success' : 'error');
    result.textContent = json.success ? '✓ Announcement published!' : '✗ Error: ' + (json.error || 'Unknown error');
    result.style.display = 'block';
    
    if (json.success) {
      e.target.reset();
      loadAnnouncements();
    }
  } catch (err) {
    result.className = 'alert error';
    result.textContent = '✗ Network error';
    result.style.display = 'block';
  }
  
  btn.disabled = false;
  btn.textContent = 'Publish Announcement';
  setTimeout(() => { result.style.display = 'none'; }, 5000);
});

async function loadAnnouncements() {
  const list = document.getElementById('announceList');
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/announcements?select=*&order=created_at.desc', {
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    const data = await res.json();
    
    if (data.length === 0) {
      list.innerHTML = '<p style="color:#666;text-align:center;padding:40px">No announcements yet</p>';
      return;
    }
    
    list.innerHTML = data.map(a => {
      const isActive = a.is_active && new Date(a.end_date) > new Date();
      return `
        <div class="list-item">
          <div class="list-content">
            <h3>${a.title}</h3>
            <p>${a.description.substring(0, 150)}${a.description.length > 150 ? '...' : ''}</p>
            <div class="list-meta">
              <span class="badge ${isActive ? 'active' : 'expired'}">${isActive ? 'Active' : 'Expired'}</span>
              <span class="badge" style="background:rgba(255,255,255,0.05);color:#888">${a.priority}</span>
              <span>Expires: ${new Date(a.end_date).toLocaleDateString()}</span>
            </div>
          </div>
          ${'<?= $admin_type ?>' === 'manager' ? '' : `<button class="btn btn-danger" onclick="deleteAnnouncement('${a.id}', '${a.title.replace(/'/g, "\\'")}')">Delete</button>`}
        </div>
      `;
    }).join('');
  } catch (err) {
    list.innerHTML = '<p style="color:#ff4d6a;text-align:center;padding:40px">Failed to load</p>';
  }
}

async function deleteAnnouncement(id, title) {
  document.getElementById('deleteText').textContent = `Delete "${title}"? This cannot be undone.`;
  document.getElementById('deleteModal').classList.add('show');
  
  document.getElementById('confirmDelete').onclick = async function() {
    closeDeleteModal();
    try {
      const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/announcements?id=eq.' + id, {
        method: 'DELETE',
        headers: {
          'apikey': '<?= SUPABASE_ANON_KEY ?>',
          'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
        }
      });
      if (res.ok) loadAnnouncements();
    } catch (err) {
      alert('Failed to delete');
    }
  };
}

function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('show');
}

loadAnnouncements();
</script>
</body>
</html>
