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
<title>Users - Hiotaku</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Space Grotesk', sans-serif; background: #050505; color: #fff; min-height: 100vh; }
.container { max-width: 1400px; margin: 0 auto; padding: 40px; }
.back { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; text-decoration: none; font-size: 0.85rem; margin-bottom: 40px; transition: all 0.3s; }
.back:hover { background: rgba(255,255,255,0.1); transform: translateX(-4px); }
h1 { font-size: 3rem; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.03em; }
.subtitle { color: #666; font-size: 1rem; margin-bottom: 40px; }
.search { padding: 12px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 0.95rem; width: 100%; max-width: 400px; margin-bottom: 24px; }
.table-wrap { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { padding: 16px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.1); white-space: nowrap; }
td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; white-space: nowrap; }
.badge.active { background: rgba(45,224,139,0.1); color: #2de08b; border: 1px solid rgba(45,224,139,0.2); }
.badge.banned { background: rgba(255,77,106,0.1); color: #ff4d6a; border: 1px solid rgba(255,77,106,0.2); }
.btn-sm { padding: 6px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; font-family: 'Space Grotesk', sans-serif; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; margin-right: 8px; white-space: nowrap; }
.btn-sm:hover { background: rgba(255,255,255,0.1); }
.btn-sm.danger { background: rgba(255,77,106,0.1); border-color: rgba(255,77,106,0.3); color: #ff4d6a; }
.btn-sm.danger:hover { background: rgba(255,77,106,0.2); }
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
.modal.show { display: flex; }
.modal-content { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.2); border-radius: 16px; padding: 32px; max-width: 500px; width: 90%; }
.modal-content h3 { font-size: 1.5rem; margin-bottom: 20px; }
.form-group { margin-bottom: 16px; }
label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; }
textarea { resize: vertical; min-height: 80px; }
.btn { padding: 12px 24px; background: #fff; color: #000; border: none; border-radius: 8px; font-family: 'Space Grotesk', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-right: 12px; }
.btn.secondary { background: rgba(255,255,255,0.1); color: #fff; }
.modal-actions { display: flex; gap: 12px; }
.modal-actions .btn { flex: 1; }
</style>
</head>
<body>
<script src="supabase-helper.js"></script>
<body>
<script src="supabase-helper.js"></script>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>Users</h1>
  <p class="subtitle">Manage user accounts and permissions</p>

  <input type="text" class="search" id="search" placeholder="Search by username or email...">

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Status</th>
          <th>Ban Info</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="userList">
        <tr><td colspan="6" style="text-align:center;padding:40px;color:#666">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div id="banModal" class="modal">
  <div class="modal-content">
    <h3>Ban User</h3>
    <form id="banForm">
      <input type="hidden" id="banUserId">
      <div class="form-group">
        <label>Ban Type</label>
        <select id="banType">
          <option value="temp">Temporary</option>
          <option value="perm">Permanent</option>
        </select>
      </div>
      <div class="form-group" id="durationField">
        <label>Duration</label>
        <select id="duration">
          <option value="1 hour">1 Hour</option>
          <option value="1 day">1 Day</option>
          <option value="7 days">7 Days</option>
          <option value="30 days">30 Days</option>
        </select>
      </div>
      <div class="form-group">
        <label>Reason</label>
        <textarea id="banReason" placeholder="Ban reason..." required></textarea>
      </div>
      <button type="submit" class="btn">Ban User</button>
      <button type="button" class="btn secondary" onclick="closeBanModal()">Cancel</button>
    </form>
  </div>
</div>

<div id="unbanModal" class="modal">
  <div class="modal-content">
    <h3>Unban User</h3>
    <p id="unbanText">Unban this user? They will regain access immediately.</p>
    <div class="modal-actions">
      <button class="btn secondary" onclick="closeUnbanModal()">Cancel</button>
      <button class="btn" id="confirmUnban">Unban</button>
    </div>
  </div>
</div>

<script>
let allUsers = [];

document.getElementById('banType').addEventListener('change', function() {
  document.getElementById('durationField').style.display = this.value === 'temp' ? 'block' : 'none';
});

async function loadUsers() {
  try {
    allUsers = await supabase.get('users', {
      'select': 'id,display_name,username,email,is_active,banned_until,ban_reason,created_at',
      'order': 'created_at.desc'
    });
    renderUsers(allUsers);
  } catch (err) {
    document.getElementById('userList').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#ff4d6a">Failed to load users</td></tr>';
  }
}

const isManager = '<?= $admin_type ?>' === 'manager';

function renderUsers(users) {
  const list = document.getElementById('userList');
  if (users.length === 0) {
    list.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:#666">No users found</td></tr>';
    return;
  }
  
  list.innerHTML = users.map(u => {
    const now = new Date();
    const bannedUntil = u.banned_until ? new Date(u.banned_until) : null;
    const isTempBanned = bannedUntil && bannedUntil > now;
    const isPermBanned = u.is_active === false;
    const isBanned = isPermBanned || isTempBanned;
    
    let status = 'Active';
    let banInfo = '-';
    
    if (isPermBanned) {
      status = 'Permanently Banned';
      banInfo = u.ban_reason || 'No reason';
    } else if (isTempBanned) {
      status = 'Temp Banned';
      const timeLeft = Math.ceil((bannedUntil - now) / (1000 * 60 * 60 * 24));
      banInfo = `${timeLeft}d left: ${u.ban_reason || 'No reason'}`;
    }
    
    return `
      <tr>
        <td><strong>${u.display_name || u.username || 'Unknown'}</strong></td>
        <td>${u.email || 'N/A'}</td>
        <td><span class="badge ${isBanned ? 'banned' : 'active'}">${status}</span></td>
        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #888; font-size: 0.85rem;">${banInfo}</td>
        <td>${new Date(u.created_at).toLocaleDateString()}</td>
        <td>
          ${isManager ? '-' : (isBanned ? 
            `<button class="btn-sm" onclick="unbanUser('${u.id}')">Unban</button>` :
            `<button class="btn-sm danger" onclick="openBanModal('${u.id}')">Ban</button>`
          )}
        </td>
      </tr>
    `;
  }).join('');
}

document.getElementById('search').addEventListener('input', function() {
  const query = this.value.toLowerCase();
  const filtered = allUsers.filter(u => 
    (u.username || '').toLowerCase().includes(query) ||
    (u.email || '').toLowerCase().includes(query) ||
    (u.display_name || '').toLowerCase().includes(query)
  );
  renderUsers(filtered);
});

function openBanModal(userId) {
  document.getElementById('banUserId').value = userId;
  document.getElementById('banModal').classList.add('show');
}

function closeBanModal() {
  document.getElementById('banModal').classList.remove('show');
  document.getElementById('banForm').reset();
}

document.getElementById('banForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const userId = document.getElementById('banUserId').value;
  const banType = document.getElementById('banType').value;
  const duration = document.getElementById('duration').value;
  const reason = document.getElementById('banReason').value.replace(/'/g, "''");
  
  let updateData;
  if (banType === 'perm') {
    updateData = { is_active: false, ban_reason: reason, banned_until: null };
  } else {
    updateData = { banned_until: new Date(Date.now() + parseDuration(duration)).toISOString(), ban_reason: reason };
  }
  
  try {
    await supabase.patch('users', updateData, {'id': `eq.${userId}`});
    
    closeBanModal();
    loadUsers();
    alert('User banned successfully');
  } catch (err) {
    alert('Failed to ban user: ' + err.message);
  }
});

async function unbanUser(userId) {
  document.getElementById('unbanModal').classList.add('show');
  
  document.getElementById('confirmUnban').onclick = async function() {
    closeUnbanModal();
    try {
      await supabase.patch('users', 
        { is_active: true, banned_until: null, ban_reason: null },
        {'id': `eq.${userId}`}
      );
      loadUsers();
    } catch (err) {
      alert('Failed to unban user: ' + err.message);
    }
  };
}

function closeUnbanModal() {
  document.getElementById('unbanModal').classList.remove('show');
}

function parseDuration(dur) {
  const [num, unit] = dur.split(' ');
  const multipliers = { hour: 3600000, day: 86400000, days: 86400000 };
  return parseInt(num) * multipliers[unit];
}

loadUsers();
</script>
</body>
</html>
