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
<title>Monitor - Hiotaku</title>
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
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 40px; }
.stat-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; }
.stat-icon { font-size: 2rem; margin-bottom: 16px; opacity: 0.7; }
.stat-value { font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; }
.stat-label { color: #888; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; }
.chart-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; margin-bottom: 40px; }
.chart-card h2 { font-size: 1.5rem; margin-bottom: 24px; }
.table-wrap { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { padding: 16px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: #888; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.1); }
td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
tr:last-child td { border-bottom: none; }
.bar-chart { display: flex; align-items: flex-end; gap: 12px; height: 200px; margin-top: 24px; }
.bar { flex: 1; background: linear-gradient(to top, #2de08b, rgba(45,224,139,0.3)); border-radius: 8px 8px 0 0; position: relative; min-height: 20px; transition: all 0.3s; }
.bar:hover { opacity: 0.8; }
.bar-label { position: absolute; bottom: -24px; left: 50%; transform: translateX(-50%); font-size: 0.7rem; color: #666; white-space: nowrap; }
.bar-value { position: absolute; top: -24px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; font-weight: 600; }
</style>
</head>
<body>
<script src="supabase-helper.js"></script>
<body>
<script src="supabase-helper.js"></script>
<body>
<div class="container">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  
  <h1>Monitor</h1>
  <p class="subtitle">System analytics and performance metrics</p>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-value" id="todayUsers">—</div>
      <div class="stat-label">Today's Active Users</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-rocket"></i></div>
      <div class="stat-value" id="todayOpens">—</div>
      <div class="stat-label">Today's App Opens</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
      <div class="stat-value" id="avgOpens">—</div>
      <div class="stat-label">Avg Opens/User</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-check"></i></div>
      <div class="stat-value" id="totalUsers">—</div>
      <div class="stat-label">Total Registered</div>
    </div>
  </div>

  <div class="chart-card">
    <h2>Last 7 Days Activity</h2>
    <div class="bar-chart" id="chart"></div>
  </div>

  <h2 style="font-size: 2rem; margin-bottom: 20px;">Top Active Users Today</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Opens Today</th>
          <th>Last Active</th>
        </tr>
      </thead>
      <tbody id="topUsers">
        <tr><td colspan="3" style="text-align:center;padding:40px;color:#666">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
async function loadStats() {
  try {
    // Today's stats
    const todayRes = await fetch('../api/proxy.php?endpoint=today_stats&date=eq.' + new Date().toISOString().split('T')[0]);
    const todayData = await todayRes.json();
    
    if (todayData.length > 0) {
      const today = todayData[0];
      document.getElementById('todayUsers').textContent = today.unique_users || 0;
      document.getElementById('todayOpens').textContent = today.total_opens || 0;
      const avg = today.unique_users > 0 ? Math.round((today.total_opens || 0) / today.unique_users) : 0;
      document.getElementById('avgOpens').textContent = avg;
    }

    // Total users
    const users = await supabase.get('users', {'select': 'id'});
    document.getElementById('totalUsers').textContent = users.length;

    // Last 7 days chart
    const chartData = await supabase.get('today_stats', {'select': '*', 'order': 'date.desc', 'limit': '7'});
    renderChart(chartData.reverse());

    // Top users today
    const topUsers = await supabase.get('today_opens', {
      'date': `eq.${new Date().toISOString().split('T')[0]}`,
      'select': '*',
      'order': 'total_opens.desc',
      'limit': '10'
    });
    renderTopUsers(topUsers);

  } catch (err) {
    console.error('Failed to load stats:', err);
  }
}

function renderChart(data) {
  const chart = document.getElementById('chart');
  if (data.length === 0) {
    chart.innerHTML = '<p style="color:#666;text-align:center">No data available</p>';
    return;
  }

  const maxValue = Math.max(...data.map(d => d.total_opens || 0));
  
  chart.innerHTML = data.map(d => {
    const height = maxValue > 0 ? ((d.total_opens || 0) / maxValue) * 100 : 10;
    const date = new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    return `
      <div class="bar" style="height: ${height}%">
        <div class="bar-value">${d.total_opens || 0}</div>
        <div class="bar-label">${date}</div>
      </div>
    `;
  }).join('');
}

function renderTopUsers(users) {
  const tbody = document.getElementById('topUsers');
  if (users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:40px;color:#666">No activity today</td></tr>';
    return;
  }

  tbody.innerHTML = users.map(u => `
    <tr>
      <td><strong>${u.user_name || 'Unknown'}</strong></td>
      <td>${u.total_opens}</td>
      <td>${new Date(u.last_opened_at).toLocaleString()}</td>
    </tr>
  `).join('');
}

loadStats();
setInterval(loadStats, 30000); // Refresh every 30 seconds
</script>
</body>
</html>
