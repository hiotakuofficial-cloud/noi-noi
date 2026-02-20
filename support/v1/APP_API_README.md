# Hiotaku App Download API Documentation

## Overview
Simple app download link management system for Hiotaku platform. Manages a single active download URL that can be retrieved and updated.

**Base URL:** `/support/v1/app.php`  
**Version:** 2.0  
**Authentication:** Dual key system  

---

## Authentication

All requests require dual authentication keys:

| Parameter | Value | Required |
|-----------|-------|----------|
| `authkey` | `nehubaby` | ✅ |
| `authkey2` | `pihupapa` | ✅ |

**Error Response:**
```json
{
  "success": false,
  "error": "Authentication failed"
}
```

---

## Rate Limiting

- **Limit:** 1 request per second per IP
- **Status Code:** `429 Too Many Requests`
- **Response:**
```json
{
  "success": false,
  "error": "Rate limit exceeded"
}
```

---

## Endpoints

### 1. Get App Download Link

**Endpoint:** `GET /support/v1/app.php?action=getapplink`

Returns the current active app download URL.

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `action` | string | ✅ | Must be `getapplink` |
| `authkey` | string | ✅ | Authentication key 1 |
| `authkey2` | string | ✅ | Authentication key 2 |

**Example Request:**
```bash
curl "http://localhost:8000/support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa"
```

**Success Response:**
```json
{
  "success": true,
  "download_link": "https://play.google.com/store/apps/details?id=com.hiotaku.app"
}
```

**Error Response (No Link Available):**
```json
{
  "success": false,
  "error": "No app download link available"
}
```

---

### 2. Update App Download URL

**Endpoint:** `GET /support/v1/app.php?action=updateurl&url={new_url}`

Updates the app download URL. Automatically deactivates the previous URL and sets the new one as active.

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `action` | string | ✅ | Must be `updateurl` |
| `url` | string (URL) | ✅ | New app download URL |
| `authkey` | string | ✅ | Authentication key 1 |
| `authkey2` | string | ✅ | Authentication key 2 |

**Example Requests:**
```bash
# APK Download Link
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://github.com/hiotakuofficial-cloud/releases/hiotaku-v3.0.apk&authkey=nehubaby&authkey2=pihupapa"

# Play Store Link
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://play.google.com/store/apps/details?id=com.hiotaku.app&authkey=nehubaby&authkey2=pihupapa"

# App Store Link
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://apps.apple.com/app/hiotaku/id123456789&authkey=nehubaby&authkey2=pihupapa"
```

**Success Response:**
```json
{
  "success": true,
  "message": "App download URL updated successfully",
  "new_url": "https://play.google.com/store/apps/details?id=com.hiotaku.app"
}
```

**Error Response (Invalid URL):**
```json
{
  "success": false,
  "error": "Valid URL required"
}
```

---

## Security Features

### Input Validation
- **URL Validation:** All URLs must pass PHP's `FILTER_VALIDATE_URL`
- **Field Validation:** Only allowed parameters accepted
- **Data Sanitization:** All inputs filtered and sanitized

### Extra Field Protection
Any unauthorized parameters will be rejected:

**Request:**
```bash
curl "http://localhost:8000/support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa&malicious_field=hack"
```

**Response:**
```json
{
  "success": false,
  "error": "Invalid fields detected",
  "invalid_fields": ["malicious_field"]
}
```

**Allowed Fields:** `authkey`, `authkey2`, `url`, `action`

---

## Error Handling

### HTTP Status Codes
| Code | Description |
|------|-------------|
| `200` | Success |
| `400` | Bad Request (validation errors) |
| `401` | Unauthorized (auth failure) |
| `429` | Too Many Requests (rate limit) |
| `500` | Internal Server Error |

### Error Response Format
```json
{
  "success": false,
  "error": "Error description",
  "additional_info": "Optional details"
}
```

---

## Database Schema

```sql
CREATE TABLE app_downloads (
  id BIGSERIAL PRIMARY KEY,
  app_url TEXT NOT NULL,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_app_downloads_active ON app_downloads(is_active);

-- Insert default app URL
INSERT INTO app_downloads (app_url) VALUES 
('https://github.com/hiotakuofficial-cloud/releases/hiotaku-v1.0.apk');
```

---

## Usage Flow

### Typical Usage Pattern:

1. **Get Current Link:**
   ```bash
   GET /support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa
   ```

2. **Update When New Version Available:**
   ```bash
   GET /support/v1/app.php?action=updateurl&url=https://new-version-url.com&authkey=nehubaby&authkey2=pihupapa
   ```

3. **Verify Update:**
   ```bash
   GET /support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa
   ```

### URL Management:
- Only **one active URL** at a time
- **Previous URLs automatically deactivated** when updating
- Supports **multiple URL types**: APK, Play Store, App Store, etc.

---

## Integration Examples

### JavaScript/Fetch
```javascript
// Get current download link
async function getAppLink() {
  const response = await fetch('/support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa');
  const result = await response.json();
  return result.download_link;
}

// Update download link
async function updateAppLink(newUrl) {
  const response = await fetch(`/support/v1/app.php?action=updateurl&url=${encodeURIComponent(newUrl)}&authkey=nehubaby&authkey2=pihupapa`);
  const result = await response.json();
  return result.success;
}
```

### PHP/cURL
```php
// Get current download link
function getAppDownloadLink() {
    $url = '/support/v1/app.php?action=getapplink&authkey=nehubaby&authkey2=pihupapa';
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    return $result['download_link'] ?? null;
}

// Update download link
function updateAppDownloadLink($newUrl) {
    $url = '/support/v1/app.php?action=updateurl&url=' . urlencode($newUrl) . '&authkey=nehubaby&authkey2=pihupapa';
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    return $result['success'] ?? false;
}
```

### Python/Requests
```python
import requests

def get_app_link():
    response = requests.get('/support/v1/app.php', params={
        'action': 'getapplink',
        'authkey': 'nehubaby',
        'authkey2': 'pihupapa'
    })
    return response.json().get('download_link')

def update_app_link(new_url):
    response = requests.get('/support/v1/app.php', params={
        'action': 'updateurl',
        'url': new_url,
        'authkey': 'nehubaby',
        'authkey2': 'pihupapa'
    })
    return response.json().get('success', False)
```

---

## Use Cases

### 1. Mobile App Updates
```bash
# When releasing new version
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://github.com/releases/hiotaku-v2.1.apk&authkey=nehubaby&authkey2=pihupapa"
```

### 2. Platform Migration
```bash
# Moving from direct APK to Play Store
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://play.google.com/store/apps/details?id=com.hiotaku&authkey=nehubaby&authkey2=pihupapa"
```

### 3. A/B Testing
```bash
# Switch between different download sources
curl "http://localhost:8000/support/v1/app.php?action=updateurl&url=https://beta.hiotaku.com/app.apk&authkey=nehubaby&authkey2=pihupapa"
```

---

## Production Considerations

### Performance
- **Rate Limiting:** 1 req/sec per IP
- **Connection Timeout:** 10 seconds
- **Single Active URL:** Optimized for fast retrieval

### Monitoring
- Error logging enabled
- HTTP status code tracking
- Rate limit monitoring

### Scalability
- Stateless design
- Database connection pooling ready
- CDN compatible URLs

---

## Changelog

### v2.0 (2026-01-09)
- Simplified to 2 actions only
- Single active URL management
- Auto-deactivation of old URLs
- Improved URL validation
- Streamlined database schema

### v1.0 (Previous)
- Complex multi-app management
- Download counters
- App name management

---

## Support

For API issues or questions:
- **Platform:** Hiotaku App Download System
- **Documentation:** This file
- **Valid Actions:** `getapplink`, `updateurl`

**Note:** This API maintains only one active download URL at a time for simplicity and reliability.
