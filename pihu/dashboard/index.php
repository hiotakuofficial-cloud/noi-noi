<?php
session_start();
require_once __DIR__ . '/../config/check_auth.php';
$admin_name = $_SESSION['admin_username'];
$admin_type = $_SESSION['admin_type'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hiotaku Control</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Space Grotesk', sans-serif;
  background: #050505;
  color: #fff;
  min-height: 100vh;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 60px 40px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 80px;
  padding-bottom: 30px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

.brand {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand img {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.2);
}

.brand-text {
  font-size: 1.8rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 24px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  transition: all 0.3s;
}

.user-badge:hover {
  border-color: rgba(255,255,255,0.3);
  background: rgba(255,255,255,0.05);
}

.user-info .name {
  font-size: 0.95rem;
  font-weight: 500;
  margin-bottom: 2px;
}

.user-info .role {
  font-size: 0.7rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.15em;
}

.logout {
  padding: 8px 20px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  border-radius: 8px;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  transition: all 0.3s;
}

.logout:hover {
  background: rgba(255,255,255,0.1);
  border-color: rgba(255,255,255,0.3);
  transform: translateY(-2px);
}

.hero {
  margin-bottom: 60px;
}

.hero .label {
  display: inline-block;
  padding: 6px 12px;
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 20px;
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  margin-bottom: 24px;
  font-weight: 500;
}

.hero h1 {
  font-size: clamp(3rem, 8vw, 6rem);
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.04em;
  margin-bottom: 20px;
}

.hero h1 span {
  display: block;
  color: #555;
}

.hero p {
  font-size: 1.1rem;
  color: #888;
  max-width: 600px;
  line-height: 1.6;
  font-weight: 300;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
  margin-bottom: 60px;
}

.card {
  aspect-ratio: 3/4;
  background: #0a0a0a;
  border: 1px solid rgba(255,255,255,0.1);
  padding: 32px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
}

.card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(255,255,255,0.08) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.3s;
  pointer-events: none;
}

.card:hover::before {
  opacity: 1;
}

.card:hover {
  transform: translateY(-10px);
  border-color: rgba(255,255,255,0.3);
  box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}

.card-top .label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-weight: 700;
  margin-bottom: 16px;
  display: block;
}

.card:nth-child(1) .label { color: #ff6b6b; }
.card:nth-child(2) .label { color: #4ecdc4; }
.card:nth-child(3) .label { color: #ffe66d; }
.card:nth-child(4) .label { color: #a8e6cf; }
.card:nth-child(5) .label { color: #ff8b94; }
.card:nth-child(6) .label { color: #c7ceea; }

.card-icon {
  font-size: 3rem;
  margin-bottom: 16px;
  display: block;
}

.card-title {
  font-size: 1.8rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin-bottom: 12px;
}

.card-desc {
  font-size: 0.85rem;
  color: #666;
  line-height: 1.5;
  margin-bottom: 20px;
}

.card-link {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  border-bottom: 1px solid rgba(255,255,255,0.2);
  display: inline-block;
  padding-bottom: 4px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.stat {
  padding: 32px;
  background: #0a0a0a;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  transition: all 0.3s;
}

.stat:hover {
  border-color: rgba(255,255,255,0.3);
  transform: translateY(-4px);
}

.stat-value {
  font-size: 3.5rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  margin-bottom: 8px;
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-weight: 500;
}

@media (max-width: 768px) {
  .container { padding: 40px 20px; }
  .header { flex-direction: column; gap: 20px; }
  .hero h1 { font-size: 3rem; }
  .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<script src="supabase-helper.js"></script>
<div class="container">
  <div class="header">
    <div class="brand">
      <img src="../assets/logo_banner.png" alt="Hiotaku" style="width:auto;height:48px;border:none">
    </div>
    <div style="display:flex;align-items:center;gap:16px">
      <div class="user-badge">
        <div class="user-info">
          <div class="name"><?= htmlspecialchars($admin_name) ?></div>
          <div class="role"><?= htmlspecialchars($admin_type) ?></div>
        </div>
      </div>
      <button class="logout" onclick="location.href='../api/logout.php'">Logout</button>
    </div>
  </div>

  <div class="hero">
    <div class="label">Control Interface</div>
    <h1>Command<span>Center</span></h1>
    <p>Unified dashboard for managing notifications, announcements, app updates, and system operations with precision and control.</p>
  </div>

  <div class="grid">
    <a href="notifications.php" class="card">
      <div class="card-top">
        <span class="label">01 / Notifications</span>
        <i class="card-icon fas fa-bell"></i>
        <h2 class="card-title">Push Alerts</h2>
      </div>
      <div>
        <p class="card-desc">Send real-time notifications to all users or specific individuals with custom messages.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="announcements.php" class="card">
      <div class="card-top">
        <span class="label">02 / Announcements</span>
        <i class="card-icon fas fa-bullhorn"></i>
        <h2 class="card-title">Broadcast</h2>
      </div>
      <div>
        <p class="card-desc">Create and manage in-app announcements with priority levels and expiration dates.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="updates.php" class="card" <?= $admin_type === 'manager' ? 'style="display:none"' : '' ?>>
      <div class="card-top">
        <span class="label">03 / App Updates</span>
        <i class="card-icon fas fa-rocket"></i>
        <h2 class="card-title">Deploy</h2>
      </div>
      <div>
        <p class="card-desc">Push new app versions and manage update distribution with forced update options.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="users.php" class="card">
      <div class="card-top">
        <span class="label">04 / Users</span>
        <i class="card-icon fas fa-users"></i>
        <h2 class="card-title">Accounts</h2>
      </div>
      <div>
        <p class="card-desc">View and manage user accounts, permissions, and access control settings.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="system.php" class="card" <?= $admin_type === 'manager' ? 'style="display:none"' : '' ?>>
      <div class="card-top">
        <span class="label">05 / System</span>
        <i class="card-icon fas fa-cog"></i>
        <h2 class="card-title">Configure</h2>
      </div>
      <div>
        <p class="card-desc">Configure services, features, and system-wide settings for optimal performance.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="status.php" class="card" <?= $admin_type === 'manager' ? 'style="display:none"' : '' ?>>
      <div class="card-top">
        <span class="label">06 / Status</span>
        <i class="card-icon fas fa-chart-line"></i>
        <h2 class="card-title">Monitor</h2>
      </div>
      <div>
        <p class="card-desc">View system health metrics, alerts, and real-time performance monitoring.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>

    <a href="admins.php" class="card" <?= $admin_type === 'manager' ? 'style="display:none"' : '' ?>>
      <div class="card-top">
        <span class="label">07 / Admins</span>
        <i class="card-icon fas fa-user-shield"></i>
        <h2 class="card-title">Admins</h2>
      </div>
      <div>
        <p class="card-desc">Create and manage admin accounts with different permission levels.</p>
        <span class="card-link">View Module →</span>
      </div>
    </a>
  </div>

  <div class="stats">
    <div class="stat">
      <div class="stat-value" id="userCount">—</div>
      <div class="stat-label">Total Users</div>
    </div>
    <div class="stat">
      <div class="stat-value" id="notifCount">—</div>
      <div class="stat-label">Notifications Sent</div>
    </div>
    <div class="stat">
      <div class="stat-value" id="announceCount">—</div>
      <div class="stat-label">Active Announcements</div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.card').forEach(card => {
  card.onmousemove = e => {
    const rect = card.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    card.style.setProperty('--x', `${x}%`);
    card.style.setProperty('--y', `${y}%`);
  };
});

supabase.get('users', {'select': 'count'})
  .then(d => document.getElementById('userCount').textContent = d[0]?.count || 0);

supabase.get('notifications', {'select': 'count'})
  .then(d => document.getElementById('notifCount').textContent = d[0]?.count || 0);

supabase.get('announcements', {'select': 'count', 'is_active': 'eq.true'})
  .then(d => document.getElementById('announceCount').textContent = d[0]?.count || 0);
</script>
</body>
</html>
