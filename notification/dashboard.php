<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check session timeout (4 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiotaku Notification Center</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(255, 140, 0, 0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header h1 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            text-align: center;
            margin-top: 10px;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .main-content {
            padding: 40px 0;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .card h2 {
            color: #FF8C00;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
        }
        
        .form-control::placeholder {
            color: #888;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF8C00 0%, #ff7700 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
            color: white;
        }
        
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .template-card {
            background: rgba(255, 140, 0, 0.1);
            border: 1px solid rgba(255, 140, 0, 0.3);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .template-card:hover {
            background: rgba(255, 140, 0, 0.2);
            transform: translateY(-2px);
        }
        
        .template-card h4 {
            color: #FF8C00;
            margin-bottom: 8px;
        }
        
        .template-card p {
            font-size: 13px;
            opacity: 0.8;
        }
        
        .user-search {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-result-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background 0.2s ease;
        }
        
        .search-result-item:hover {
            background: rgba(255, 140, 0, 0.1);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .log-container {
            background: #000;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 5px 0;
        }
        
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #17a2b8; }
        .log-warning { color: #ffc107; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 140, 0, 0.1);
            border: 1px solid rgba(255, 140, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #FF8C00;
        }
        
        .stat-label {
            margin-top: 5px;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .template-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 3px solid #FF8C00;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-bell"></i> Hiotaku Notification Center</h1>
                    <p>Send custom notifications to your app users with ease</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;">
                        <i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </p>
                    <a href="logout.php" style="color: white; text-decoration: none; font-size: 14px; margin-top: 5px; display: inline-block;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <!-- Stats Section -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers">-</div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeTokens">-</div>
                    <div class="stat-label">Active FCM Tokens</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="sentToday">-</div>
                    <div class="stat-label">Sent Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalSent">-</div>
                    <div class="stat-label">Total Sent</div>
                </div>
            </div>

            <div class="grid">
                <!-- Send Notification Form -->
                <div class="card">
                    <h2><i class="fas fa-paper-plane"></i> Send Notification</h2>
                    
                    <form id="notificationForm">
                        <div class="form-group">
                            <label>Send To:</label>
                            <select class="form-control" id="sendType" onchange="toggleUserSearch()">
                                <option value="all">All Users</option>
                                <option value="specific">Specific User</option>
                            </select>
                        </div>

                        <div class="form-group user-search" id="userSearchGroup" style="display: none;">
                            <label>Search User:</label>
                            <input type="text" class="form-control" id="userSearch" placeholder="Type username or email..." oninput="searchUsers()">
                            <div class="search-results" id="searchResults"></div>
                            <input type="hidden" id="selectedUserId">
                        </div>

                        <div class="form-group">
                            <label>Notification Title:</label>
                            <input type="text" class="form-control" id="notificationTitle" placeholder="Enter notification title..." required>
                        </div>

                        <div class="form-group">
                            <label>Notification Body:</label>
                            <textarea class="form-control" id="notificationBody" placeholder="Enter notification message..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Notification Type:</label>
                            <select class="form-control" id="notificationType" onchange="updateClickAction()">
                                <option value="general">General</option>
                                <option value="announcement">Announcement</option>
                                <option value="movie">New Movie</option>
                                <option value="update">App Update</option>
                                <option value="promotion">Promotion</option>
                                <option value="reminder">Reminder</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Click Action (Screen to Open):</label>
                            <select class="form-control" id="clickScreen">
                                <option value="/home">Home Screen</option>
                                <option value="/movies">Movies List</option>
                                <option value="/movie-details">Movie Details</option>
                                <option value="/profile">User Profile</option>
                                <option value="/announcements">Announcements</option>
                                <option value="/settings">Settings</option>
                            </select>
                        </div>

                        <div class="form-group" id="movieIdGroup" style="display: none;">
                            <label>Movie ID (for movie notifications):</label>
                            <input type="text" class="form-control" id="movieId" placeholder="Enter movie ID...">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-send"></i> Send Notification
                        </button>
                    </form>
                </div>

                <!-- Templates Section -->
                <div class="card">
                    <h2><i class="fas fa-templates"></i> Quick Templates</h2>
                    
                    <div class="template-grid">
                        <div class="template-card" onclick="useTemplate('welcome')">
                            <h4><i class="fas fa-hand-wave"></i> Welcome</h4>
                            <p>Welcome new users to the app</p>
                        </div>
                        
                        <div class="template-card" onclick="useTemplate('update')">
                            <h4><i class="fas fa-download"></i> App Update</h4>
                            <p>Notify about new app version</p>
                        </div>
                        
                        <div class="template-card" onclick="useTemplate('movie')">
                            <h4><i class="fas fa-film"></i> New Movie</h4>
                            <p>Announce new movie releases</p>
                        </div>
                        
                        <div class="template-card" onclick="useTemplate('maintenance')">
                            <h4><i class="fas fa-tools"></i> Maintenance</h4>
                            <p>Server maintenance notice</p>
                        </div>
                        
                        <div class="template-card" onclick="useTemplate('promotion')">
                            <h4><i class="fas fa-gift"></i> Promotion</h4>
                            <p>Special offers and deals</p>
                        </div>
                        
                        <div class="template-card" onclick="useTemplate('reminder')">
                            <h4><i class="fas fa-clock"></i> Reminder</h4>
                            <p>Remind users to check app</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <h2><i class="fas fa-history"></i> Activity Log</h2>
                <div class="log-container" id="activityLog">
                    <div class="log-entry log-info">[System] Notification center loaded</div>
                </div>
                <button class="btn btn-secondary" onclick="clearLog()" style="margin-top: 15px;">
                    <i class="fas fa-trash"></i> Clear Log
                </button>
            </div>

            <!-- Loading Indicator -->
            <div class="loading" id="loadingIndicator">
                <div class="spinner"></div>
                <p>Sending notification...</p>
            </div>
        </div>
    </div>

    <script src="notification-center.js"></script>
</body>
</html>
