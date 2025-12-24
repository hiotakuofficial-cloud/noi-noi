# Hiotaku Bot API

A simple API endpoint for AI bots to search anime content from Hiotaku platform.

## 🚀 Quick Start

### Endpoint
```
POST /hisu/model/v1/search.php
```

### Authentication
```
Authorization: Bearer afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7
Content-Type: application/json
```

### Request Body
```json
{
    "who": "im-hisu",
    "anime": "naruto",
    "tool": "search-anime"
}
```

### Response
```json
{
    "success": true,
    "query": "naruto",
    "total": 5,
    "results": [
        {
            "id": "road-of-naruto-18220",
            "title": "Road of Naruto",
            "poster": "https://cdn.noitatnemucod.net/thumbnail/300x400/100/fd414879634ea83ad2c4fc1c33e8ac43.jpg",
            "type": "english",
            "source": "hiotaku"
        }
    ]
}
```

## 📝 Usage Examples

### cURL
```bash
curl -X POST "https://your-domain.com/hisu/model/v1/search.php" \
  -H "Authorization: Bearer afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7" \
  -H "Content-Type: application/json" \
  -d '{
    "who": "im-hisu",
    "anime": "one piece",
    "tool": "search-anime"
  }'
```

### JavaScript
```javascript
const response = await fetch('/hisu/model/v1/search.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    who: 'im-hisu',
    anime: 'demon slayer',
    tool: 'search-anime'
  })
});

const data = await response.json();
console.log(data.results);
```

### Python
```python
import requests

url = "https://your-domain.com/hisu/model/v1/search.php"
headers = {
    "Authorization": "Bearer afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7",
    "Content-Type": "application/json"
}
data = {
    "who": "im-hisu",
    "anime": "attack on titan",
    "tool": "search-anime"
}

response = requests.post(url, headers=headers, json=data)
results = response.json()
print(results['results'])
```

## 🔧 Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `who` | string | ✅ | Must be `"im-hisu"` |
| `anime` | string | ✅ | Anime name to search |
| `tool` | string | ✅ | Must be `"search-anime"` |

## 📊 Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Request status |
| `query` | string | Search query used |
| `total` | number | Total results count |
| `results` | array | Array of anime objects |

### Result Object
| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique anime ID |
| `title` | string | Anime title |
| `poster` | string | Poster image URL |
| `type` | string | Content type (`english`) |
| `source` | string | Always `"hiotaku"` |

## 🚨 Error Responses

### Unauthorized
```json
{
    "error": "Unauthorized"
}
```

### Invalid Model
```json
{
    "error": "model isnt working currently"
}
```

### Invalid Parameters
```json
{
    "error": "Invalid request parameters"
}
```

## 🌐 Additional APIs

For Hindi content, use the separate endpoint:
```
GET /hindiv2.php?action=search&q=naruto&token=YOUR_TOKEN
```

## 📋 Notes

- Only returns English/Sub+Dub content
- For Hindi dubbed content, use `hindiv2.php` separately
- All responses include `"source": "hiotaku"` branding
- Rate limiting may apply in production

## 🔒 Security

- Bearer token authentication required
- Token validation using secure comparison
- Input sanitization applied

---

**Made with ❤️ by Hiotaku Team**
