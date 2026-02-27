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
<title>Admins - Hiotaku</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Space Grotesk', sans-serif; background: #050505; color: #fff; min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 40px; }
.back { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; text-decoration: none; font-size: 0.85rem; margin-bottom: 40px; transition: all 0.3s; }
.back:hover { background: rgba(255,255,255,0.1); transform: translateX(-4px); }
h1 { font-size: 3rem; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.03em; }
.subtitle { color: #666; font-size: 1rem; margin-bottom: 40px; }
.form-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; margin-bottom: 40px; }
.form-group { margin-bottom: 20px; }
label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px; font-weight: 500; }
input, select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 0.95rem; transition: all 0.3s; }
input:focus, select:focus { outline: none; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
.btn { padding: 14px 32px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.1em; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,255,255,0.2); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.alert { padding: 16px; border-radius: 8px; margin-top: 20px; font-size: 0.9rem; display: none; }
.alert.success { background: rgba(45,224,139,0.1); border: 1px solid rgba(45,224,139,0.3); color: #2de08b; }
.alert.error { background: rgba(255,77,106,0.1); border: 1px solid rgba(255,77,106,0.3); color: #ff4d6a; }
.table-wrap { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { padding: 16px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.1); }
td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; }
.badge.admin { background: rgba(255,215,0,0.1); color: #ffd700; border: 1px solid rgba(255,215,0,0.2); }
.badge.manager { background: rgba(100,149,237,0.1); color: #6495ed; border: 1px solid rgba(100,149,237,0.2); }
.badge.active { background: rgba(45,224,139,0.1); color: #2de08b; border: 1px solid rgba(45,224,139,0.2); }
.badge.banned { background: rgba(255,77,106,0.1); color: #ff4d6a; border: 1px solid rgba(255,77,106,0.2); }
.btn-sm { padding: 6px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; font-family: 'Space Grotesk', sans-serif; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; margin-right: 8px; }
.btn-sm:hover { background: rgba(255,255,255,0.1); }
.btn-sm.danger { background: rgba(255,77,106,0.1); border-color: rgba(255,77,106,0.3); color: #ff4d6a; }
.btn-sm.danger:hover { background: rgba(255,77,106,0.2); }
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
  
  <h1>Admin Management</h1>
  <p class="subtitle">Create and manage admin accounts</p>

  <div class="form-card">
    <h2 style="font-size: 1.5rem; margin-bottom: 24px;">Create New Admin</h2>
    <form id="adminForm">
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="username" placeholder="admin_username" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" id="password" placeholder="Strong password" required minlength="8">
      </div>

      <div class="form-group">
        <label>User Type</label>
        <select id="userType">
          <option value="admin">Admin (Full Access)</option>
          <option value="manager">Manager (Limited Access)</option>
        </select>
      </div>

      <button type="submit" class="btn" id="submitBtn">Create Admin</button>
      <div id="result" class="alert"></div>
    </form>
  </div>

  <h2 style="font-size: 2rem; margin-bottom: 20px;">Admin List</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>Type</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="adminList">
        <tr><td colspan="5" style="text-align:center;padding:40px;color:#666">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h3>Delete Admin</h3>
    <p id="deleteText">Are you sure you want to delete this admin?</p>
    <div class="modal-actions">
      <button class="btn secondary" onclick="closeDeleteModal()">Cancel</button>
      <button class="btn danger" id="confirmDelete">Delete</button>
    </div>
  </div>
</div>

<div id="banModal" class="modal">
  <div class="modal-content">
    <h3 id="banTitle">Ban Admin</h3>
    <p id="banText">Are you sure?</p>
    <div class="modal-actions">
      <button class="btn secondary" onclick="closeBanModal()">Cancel</button>
      <button class="btn danger" id="confirmBan">Confirm</button>
    </div>
  </div>
</div>

<script>
async function loadAdmins() {
  try {
    const res = await fetch('<?= SUPABASE_URL ?>/rest/v1/admins?select=*&order=created_at.desc', {
      headers: {
        'apikey': '<?= SUPABASE_ANON_KEY ?>',
        'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
      }
    });
    const admins = await res.json();
    
    const list = document.getElementById('adminList');
    if (admins.length === 0) {
      list.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:#666">No admins found</td></tr>';
      return;
    }
    
    list.innerHTML = admins.map(a => `
      <tr>
        <td><strong>${a.username}</strong></td>
        <td><span class="badge ${a.user_type}">${a.user_type}</span></td>
        <td><span class="badge ${a.status}">${a.status}</span></td>
        <td>${new Date(a.created_at).toLocaleDateString()}</td>
        <td>
          ${a.status === 'active' ? 
            `<button class="btn-sm danger" onclick="toggleStatus('${a.id}', 'banned')">Ban</button>` :
            `<button class="btn-sm" onclick="toggleStatus('${a.id}', 'active')">Unban</button>`
          }
          <button class="btn-sm danger" onclick="deleteAdmin('${a.id}', '${a.username}')"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    document.getElementById('adminList').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:#ff4d6a">Failed to load admins</td></tr>';
  }
}

document.getElementById('adminForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const result = document.getElementById('result');
  
  btn.disabled = true;
  btn.textContent = 'Creating...';
  
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;
  const userType = document.getElementById('userType').value;
  
  try {
    const res = await fetch('../api/create_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password, user_type: userType })
    });
    const json = await res.json();
    
    result.className = 'alert ' + (json.success ? 'success' : 'error');
    result.textContent = json.success ? '✓ Admin created successfully!' : '✗ Error: ' + (json.error || 'Unknown error');
    result.style.display = 'block';
    
    if (json.success) {
      e.target.reset();
      loadAdmins();
    }
  } catch (err) {
    result.className = 'alert error';
    result.textContent = '✗ Network error';
    result.style.display = 'block';
  }
  
  btn.disabled = false;
  btn.textContent = 'Create Admin';
  setTimeout(() => { result.style.display = 'none'; }, 5000);
});

async function toggleStatus(id, newStatus) {
  document.getElementById('banTitle').textContent = newStatus === 'banned' ? 'Ban Admin' : 'Unban Admin';
  document.getElementById('banText').textContent = newStatus === 'banned' ? 'Ban this admin? They will be logged out immediately.' : 'Unban this admin? They will regain access.';
  document.getElementById('banModal').classList.add('show');
  
  document.getElementById('confirmBan').onclick = async function() {
    closeBanModal();
    try {
      await fetch('<?= SUPABASE_URL ?>/rest/v1/admins?id=eq.' + id, {
        method: 'PATCH',
        headers: {
          'apikey': '<?= SUPABASE_ANON_KEY ?>',
          'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>',
          'Content-Type': 'application/json',
          'Prefer': 'return=minimal'
        },
        body: JSON.stringify({ status: newStatus })
      });
      loadAdmins();
    } catch (err) {
      alert('Failed to update status');
    }
  };
}

function closeBanModal() {
  document.getElementById('banModal').classList.remove('show');
}

async function deleteAdmin(id, username) {
  document.getElementById('deleteText').textContent = `Delete admin "${username}"? This cannot be undone!`;
  document.getElementById('deleteModal').classList.add('show');
  
  document.getElementById('confirmDelete').onclick = async function() {
    closeDeleteModal();
    try {
      await fetch('<?= SUPABASE_URL ?>/rest/v1/admins?id=eq.' + id, {
        method: 'DELETE',
        headers: {
          'apikey': '<?= SUPABASE_ANON_KEY ?>',
          'Authorization': 'Bearer <?= SUPABASE_ANON_KEY ?>'
        }
      });
      loadAdmins();
    } catch (err) {
      alert('Failed to delete admin');
    }
  };
}

function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('show');
}

loadAdmins();
</script>
</body>
</html>
