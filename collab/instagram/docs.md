# Instagram Analytics API Documentation

## 🚀 Overview
Secure Instagram analytics API that scrapes data from SocialStats.info with advanced protection layers.

## 📁 File Structure
```
instagram/
├── insta.php              # Main API endpoint
├── security/
│   ├── layer.php          # Security functions
│   ├── rate_limit.json    # Rate limiting data
│   ├── security.log       # Security events
│   └── blocked_ips.json   # Blocked IPs
└── docs.md               # This documentation
```

## 🔐 Authentication
API requires 3 keys for access:

| Parameter | Value |
|-----------|-------|
| `barbeer` | `donttrytoscrapemotherfucker` |
| `key1` | `hellowbabywhatsupareyougood?` |
| `key2` | `gonnafuckyourassiftriedtoscrape` |

## 📊 API Endpoint

### Request
```
GET /instagram/insta.php
```

### Required Parameters
- `action=insta` - API action
- `username` - Instagram username (without @)
- `barbeer` - Authentication key 1
- `key1` - Authentication key 2  
- `key2` - Authentication key 3

### Example Request
```bash
curl -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
     -H "Referer: http://localhost:8004/" \
     "http://localhost:8004/instagram/insta.php?action=insta&username=olds.fx&barbeer=donttrytoscrapemotherfucker&key1=hellowbabywhatsupareyougood?&key2=gonnafuckyourassiftriedtoscrape"
```

## 📋 Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    "username": "olds.fx",
    "name": "Ruin",
    "bio": "inactive !",
    "followers": 4222,
    "uploads": 138,
    "engagement": "-0.01%",
    "status": "inactive !",
    "profile_url": "https://instagram.com/olds.fx",
    "profile_image": "https://ig-cdn.link?q=..."
  }
}
```

### Error Response
```json
{
  "error": "Error message"
}
```

## 🛡️ Security Features

### 1. Rate Limiting
- **Limit:** 10 requests per minute per IP
- **Window:** 60 seconds
- **Response:** `429 - Rate limit exceeded`

### 2. Input Validation
- **Username:** 1-30 chars, alphanumeric + dots/underscores
- **Action:** Must be 'insta'
- **Keys:** 10-100 characters length

### 3. User Agent Filtering
Blocks these user agents:
- curl, wget, python
- bot, crawler, spider, scraper

### 4. IP Blocking
- **Duration:** 24 hours
- **Triggers:** Invalid keys, suspicious activity
- **Storage:** JSON file

### 5. Security Logging
All events logged with:
- Timestamp
- IP address
- Event type
- Details

## ⚠️ Error Codes

| Code | Error | Description |
|------|-------|-------------|
| 400 | Invalid action parameter | Wrong action value |
| 400 | Invalid username format | Username validation failed |
| 400 | Invalid key format | Key validation failed |
| 403 | Give Your Ass Ill Give Assescs To My Api | Wrong API keys |
| 403 | Access denied | Blocked user agent |
| 403 | Your IP is blocked | IP in blocklist |
| 429 | Rate limit exceeded | Too many requests |
| 404 | Failed to fetch Instagram data | Profile not found |

## 🔄 How It Works

### Data Flow
1. **Security Check** → IP blocking, rate limiting, user agent
2. **Input Validation** → Sanitize and validate all inputs
3. **Authentication** → Verify 3 API keys
4. **Data Scraping** → Fetch from SocialStats.info
5. **Response** → Return JSON data

### Data Source
- **Provider:** SocialStats.info
- **Method:** HTML scraping with regex
- **Update:** Real-time (no caching)
- **Coverage:** Public Instagram profiles

### Security Layers
```
Request → IP Check → Rate Limit → User Agent → Input Validation → Auth → Data
```

## 📈 Usage Examples

### Valid Request
```bash
# Success - Returns full profile data
curl -H "User-Agent: Mozilla/5.0..." \
     "...?action=insta&username=cristiano&barbeer=...&key1=...&key2=..."
```

### Invalid Requests
```bash
# Wrong keys
curl "...?action=insta&username=test&barbeer=wrong&key1=...&key2=..."
# Response: {"error": "Give Your Ass Ill Give Assescs To My Api"}

# Bot user agent
curl "...?action=insta&username=test&barbeer=...&key1=...&key2=..."
# Response: {"error": "Access denied"}

# Invalid username
curl "...?action=insta&username=test@#$&barbeer=...&key1=...&key2=..."
# Response: {"error": "Invalid username format"}
```

## 🔧 Configuration

### Environment Variables (.env)
```
BARBEER_KEY=donttrytoscrapemotherfucker
KEY_1=hellowbabywhatsupareyougood?
KEY_2=gonnafuckyourassiftriedtoscrape
```

### Security Settings
- **Rate Limit:** 10 req/min (configurable in layer.php)
- **Block Duration:** 24 hours (configurable)
- **Max Username Length:** 30 chars
- **Key Length:** 10-100 chars

## 📊 Monitoring

### Log Files
- `security/security.log` - All security events
- `security/rate_limit.json` - Rate limiting data
- `security/blocked_ips.json` - Blocked IP addresses

### Log Format
```
[2026-01-15 10:25:00] IP: 127.0.0.1 | Event: SUCCESSFUL_AUTH | Details: Username: olds.fx
[2026-01-15 10:25:30] IP: 192.168.1.100 | Event: INVALID_API_KEYS | Details: Failed authentication
```

## 🚀 Performance
- **Response Time:** 2-3 seconds
- **Concurrent Users:** Unlimited (rate limited)
- **Data Freshness:** Real-time
- **Uptime:** Depends on SocialStats.info

## 🔒 Best Practices
1. Always use proper User-Agent headers
2. Include Referer header for better success rate
3. Respect rate limits to avoid blocking
4. Monitor security logs regularly
5. Keep API keys secure and rotate periodically

---
**⚡ Secure Instagram Analytics API - Built for Performance & Security**
