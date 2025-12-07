/**
 * Hiotaku Notification Center JavaScript
 */

let users = [];
let selectedUser = null;

// Templates
const templates = {
    welcome: {
        title: "🎉 Welcome to Hiotaku!",
        body: "Welcome to Hiotaku! Discover amazing anime and movies. Start exploring now!",
        type: "general"
    },
    update: {
        title: "📱 App Update Available",
        body: "A new version of Hiotaku is available! Update now to get the latest features and improvements.",
        type: "update"
    },
    movie: {
        title: "🎬 New Movie Added!",
        body: "Check out the latest movie addition to our collection. Don't miss out on the action!",
        type: "announcement"
    },
    maintenance: {
        title: "🔧 Scheduled Maintenance",
        body: "We'll be performing maintenance from 2:00 AM to 4:00 AM. The app may be temporarily unavailable.",
        type: "announcement"
    },
    promotion: {
        title: "🎁 Special Offer!",
        body: "Limited time offer! Get premium features at 50% off. Offer valid until midnight!",
        type: "promotion"
    },
    reminder: {
        title: "👋 We Miss You!",
        body: "Haven't watched anything lately? Check out our new recommendations just for you!",
        type: "reminder"
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadUsers();
    
    // Setup form submission
    document.getElementById('notificationForm').addEventListener('submit', handleFormSubmit);
    
    addLog('System initialized successfully', 'success');
});

// Load statistics
async function loadStats() {
    try {
        const response = await fetch('api/stats.php');
        const stats = await response.json();
        
        if (stats.success) {
            document.getElementById('totalUsers').textContent = stats.data.total_users || 0;
            document.getElementById('activeTokens').textContent = stats.data.active_tokens || 0;
            document.getElementById('sentToday').textContent = stats.data.sent_today || 0;
            document.getElementById('totalSent').textContent = stats.data.total_sent || 0;
        }
    } catch (error) {
        addLog('Failed to load statistics: ' + error.message, 'error');
    }
}

// Load users for search
async function loadUsers() {
    try {
        const response = await fetch('api/users.php');
        const result = await response.json();
        
        if (result.success) {
            users = result.data;
            addLog(`Loaded ${users.length} users`, 'info');
        }
    } catch (error) {
        addLog('Failed to load users: ' + error.message, 'error');
    }
}

// Toggle user search visibility
function toggleUserSearch() {
    const sendType = document.getElementById('sendType').value;
    const userSearchGroup = document.getElementById('userSearchGroup');
    
    if (sendType === 'specific') {
        userSearchGroup.style.display = 'block';
    } else {
        userSearchGroup.style.display = 'none';
        selectedUser = null;
        document.getElementById('selectedUserId').value = '';
    }
}

// Search users
function searchUsers() {
    const query = document.getElementById('userSearch').value.toLowerCase();
    const resultsContainer = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsContainer.style.display = 'none';
        return;
    }
    
    const filteredUsers = users.filter(user => 
        user.username.toLowerCase().includes(query) || 
        user.email.toLowerCase().includes(query) ||
        user.display_name.toLowerCase().includes(query)
    );
    
    if (filteredUsers.length > 0) {
        resultsContainer.innerHTML = filteredUsers.map(user => `
            <div class="search-result-item" onclick="selectUser('${user.id}', '${user.username}', '${user.email}')">
                <strong>${user.username}</strong> (${user.display_name})<br>
                <small style="opacity: 0.7;">${user.email}</small>
            </div>
        `).join('');
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.innerHTML = '<div class="search-result-item">No users found</div>';
        resultsContainer.style.display = 'block';
    }
}

// Select user from search results
function selectUser(userId, username, email) {
    selectedUser = { id: userId, username: username, email: email };
    document.getElementById('selectedUserId').value = userId;
    document.getElementById('userSearch').value = `${username} (${email})`;
    document.getElementById('searchResults').style.display = 'none';
    
    addLog(`Selected user: ${username}`, 'info');
}

// Use template
function useTemplate(templateKey) {
    const template = templates[templateKey];
    if (template) {
        document.getElementById('notificationTitle').value = template.title;
        document.getElementById('notificationBody').value = template.body;
        document.getElementById('notificationType').value = template.type;
        
        addLog(`Applied template: ${templateKey}`, 'info');
    }
}

// Handle form submission
async function handleFormSubmit(event) {
    event.preventDefault();
    
    const sendType = document.getElementById('sendType').value;
    const title = document.getElementById('notificationTitle').value;
    const body = document.getElementById('notificationBody').value;
    const type = document.getElementById('notificationType').value;
    const userId = document.getElementById('selectedUserId').value;
    
    // Validation
    if (sendType === 'specific' && !userId) {
        addLog('Please select a user for specific notification', 'error');
        return;
    }
    
    if (!title || !body) {
        addLog('Please fill in title and body', 'error');
        return;
    }
    
    // Show loading
    document.getElementById('loadingIndicator').style.display = 'block';
    
    try {
        const payload = {
            send_type: sendType,
            title: title,
            body: body,
            type: type,
            user_id: sendType === 'specific' ? userId : null
        };
        
        const response = await fetch('api/dashboard_send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            addLog(`✅ Notification sent successfully! Recipients: ${result.recipients_count}`, 'success');
            
            // Clear form
            document.getElementById('notificationForm').reset();
            document.getElementById('userSearchGroup').style.display = 'none';
            selectedUser = null;
            
            // Reload stats
            loadStats();
        } else {
            addLog(`❌ Failed to send notification: ${result.error}`, 'error');
        }
    } catch (error) {
        addLog(`❌ Network error: ${error.message}`, 'error');
    } finally {
        // Hide loading
        document.getElementById('loadingIndicator').style.display = 'none';
    }
}

// Add log entry
function addLog(message, type = 'info') {
    const logContainer = document.getElementById('activityLog');
    const timestamp = new Date().toLocaleTimeString();
    
    const logEntry = document.createElement('div');
    logEntry.className = `log-entry log-${type}`;
    logEntry.innerHTML = `[${timestamp}] ${message}`;
    
    logContainer.appendChild(logEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

// Clear log
function clearLog() {
    const logContainer = document.getElementById('activityLog');
    logContainer.innerHTML = '<div class="log-entry log-info">[System] Log cleared</div>';
}

// Hide search results when clicking outside
document.addEventListener('click', function(event) {
    const searchResults = document.getElementById('searchResults');
    const userSearch = document.getElementById('userSearch');
    
    if (!userSearch.contains(event.target) && !searchResults.contains(event.target)) {
        searchResults.style.display = 'none';
    }
});

// Auto-refresh stats every 30 seconds
setInterval(loadStats, 30000);
