# Hiotaku Support API Documentation

## Overview
Production-grade support ticket management system for Hiotaku platform with enterprise-level security and error handling.

**Base URL:** `/support/v1/index.php`  
**Version:** 1.0  
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

### 1. Submit Support Request

**Endpoint:** `POST /support/v1/index.php?action=support`

**Parameters:**
| Field | Type | Max Length | Required | Description |
|-------|------|------------|----------|-------------|
| `username` | string | 50 | ✅ | User's display name |
| `userId` | string | 20 | ✅ | Unique user identifier |
| `message` | string | 1000 | ✅ | Support request message |
| `sender` | string | - | ❌ | `user`, `support`, `system` (default: `user`) |

**Example Request:**
```bash
curl -X POST "/support/v1/index.php?action=support" \
  -d "authkey=nehubaby&authkey2=pihupapa&username=john_doe&userId=12345&message=Login issue with my account"
```

**Success Response:**
```json
{
  "success": true,
  "message": "Support request submitted successfully",
  "support_ticket": "HT8242395470",
  "ticket_id": 22,
  "timestamp": "2026-01-09T12:07:06.891902+00:00"
}
```

**Error Responses:**
```json
// Missing fields
{
  "success": false,
  "error": "Required fields missing",
  "required": ["username", "userId", "message"]
}

// Field length exceeded
{
  "success": false,
  "error": "Field length exceeded",
  "limits": {"username": 50, "userId": 20, "message": 1000}
}
```

---

### 2. Get Support Messages

**Endpoint:** `GET /support/v1/index.php?action=get`

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `support` | string | ✅ | `all` or specific `userId` |
| `limit` | integer | ❌ | Max records (default: 50, max: 100) |

**Example Requests:**
```bash
# Get all messages
curl "/support/v1/index.php?action=get&support=all&authkey=nehubaby&authkey2=pihupapa"

# Get user-specific messages
curl "/support/v1/index.php?action=get&support=12345&authkey=nehubaby&authkey2=pihupapa&limit=10"
```

**Success Response:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 22,
      "username": "john_doe",
      "user_id": "12345",
      "sender": "user",
      "message": "Login issue with my account",
      "created_at": "2026-01-09T12:07:06.891902+00:00"
    }
  ],
  "count": 1,
  "limit": 50
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Support parameter required",
  "valid_values": ["all", "user_id"]
}
```

---

### 3. Delete Support Ticket

**Endpoint:** `DELETE /support/v1/index.php?action=delete`

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `supportId` | integer | ✅ | Ticket ID to delete |

**Example Request:**
```bash
curl -X DELETE "/support/v1/index.php?action=delete&supportId=22&authkey=nehubaby&authkey2=pihupapa"
```

**Success Response:**
```json
{
  "success": true,
  "message": "Support ticket deleted successfully",
  "deleted_id": 22
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Valid numeric support ID required"
}
```

---

## Security Features

### Input Validation
- **Field Length Limits:** Enforced on all inputs
- **Required Field Validation:** Missing fields rejected
- **Data Sanitization:** All inputs filtered and sanitized

### Extra Field Protection
Any unauthorized parameters will be rejected:

**Request:**
```bash
curl -X POST "/support/v1/index.php?action=support" \
  -d "authkey=nehubaby&authkey2=pihupapa&username=test&userId=123&message=help&malicious_field=hack"
```

**Response:**
```json
{
  "success": false,
  "error": "Invalid fields detected",
  "invalid_fields": ["malicious_field"]
}
```

**Allowed Fields:** `authkey`, `authkey2`, `username`, `userId`, `message`, `sender`, `supportId`, `support`, `action`

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

## Support Ticket Format

**Pattern:** `HT` + 10-digit random number  
**Example:** `HT8242395470`

- **HT:** Hiotaku prefix
- **10 digits:** Unique identifier

---

## Database Schema

```sql
CREATE TABLE support_messages (
  id BIGSERIAL PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  user_id VARCHAR(50) NOT NULL,
  sender VARCHAR(50) NOT NULL CHECK (sender IN ('user', 'support', 'system')),
  message TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_support_user ON support_messages(user_id);
CREATE INDEX idx_support_username ON support_messages(username);
```

---

## Integration Examples

### JavaScript/Fetch
```javascript
// Submit support request
const response = await fetch('/support/v1/index.php?action=support', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'authkey=nehubaby&authkey2=pihupapa&username=john&userId=123&message=Help needed'
});
const result = await response.json();
console.log(result.support_ticket);
```

### PHP/cURL
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '/support/v1/index.php?action=support');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'authkey' => 'nehubaby',
    'authkey2' => 'pihupapa',
    'username' => 'john',
    'userId' => '123',
    'message' => 'Help needed'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$result = json_decode($response, true);
```

### Python/Requests
```python
import requests

response = requests.post('/support/v1/index.php?action=support', data={
    'authkey': 'nehubaby',
    'authkey2': 'pihupapa',
    'username': 'john',
    'userId': '123',
    'message': 'Help needed'
})
result = response.json()
print(result['support_ticket'])
```

---

## Production Considerations

### Performance
- **Rate Limiting:** 1 req/sec per IP
- **Connection Timeout:** 5 seconds
- **Request Timeout:** 10 seconds
- **Max Records:** 100 per request

### Monitoring
- Error logging enabled
- HTTP status code tracking
- Rate limit monitoring

### Scalability
- Stateless design
- Database connection pooling ready
- Horizontal scaling compatible

---

## Changelog

### v1.0 (2026-01-09)
- Initial production release
- Dual authentication system
- Rate limiting implementation
- Input validation and sanitization
- Extra field protection
- Comprehensive error handling
- Support ticket generation
- Full CRUD operations

---

## Support

For API issues or questions:
- **Platform:** Hiotaku Support System
- **Documentation:** This file
- **Error Logs:** Check server logs for detailed error information

**Note:** This API is production-ready with enterprise-grade security and error handling.
