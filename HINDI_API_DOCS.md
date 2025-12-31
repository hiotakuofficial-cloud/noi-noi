# 🎌 Hindi Anime API Documentation

## Overview
Two Hindi anime streaming APIs providing content from different sources:
- **hindi.php** - Hinoplex.com content (dubbed & subbed)
- **hindiv2.php** - AnimixStream.com content (dubbed only)

## 🔐 Authentication
**Required Token:** `afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7`

### Token Methods:
```bash
# Query parameter
?token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7

# Header
Authorization: Bearer afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7

# POST body
token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7
```

---

## 📡 hindi.php API

### Base URL: `/hindi.php`
**Source:** Hinoplex.com (WordPress API)

### Available Actions:
- `home` - Mixed content (25 items)
- `hindi` - All dubbed content (500+ items)  
- `subbed` - All subbed content (500+ items)
- `info` - Anime details
- `search` - Search anime
- `getep` - Get dubbed episodes with URLs
- `getsep` - Get subbed episodes with URLs

### Endpoints

#### 1. Home Content
```bash
GET /hindi.php?action=home&token=TOKEN
```
**Returns:** 25 mixed dubbed/subbed anime

#### 2. Hindi Dubbed Content
```bash
GET /hindi.php?action=hindi&token=TOKEN
```
**Returns:** All Hindi dubbed anime (500+ items)

#### 3. Hindi Subbed Content
```bash
GET /hindi.php?action=subbed&token=TOKEN
```
**Returns:** All Hindi subbed anime (500+ items)

#### 4. Anime Information
```bash
GET /hindi.php?action=info&id=12345&token=TOKEN
```
**Response Format:**
```json
{
  "id": 12345,
  "title": "Naruto",
  "name": "Naruto",
  "genres": "Action, Adventure",
  "language": "Hindi Dubbed",
  "quality": "HD",
  "synopsis": "Story description...",
  "thumbnail": "https://hinoplex.com/image.jpg"
}
```

#### 5. Search Anime
```bash
GET /hindi.php?action=search&q=naruto&token=TOKEN
```
**Returns:** Search results (25 items max)

#### 6. Get Episodes (Dubbed)
```bash
GET /hindi.php?action=getep&id=12345&token=TOKEN
```
**Response Format:**
```json
[
  {
    "episode": "1",
    "title": "Episode 1",
    "urls": ["https://stream1.com", "https://stream2.com"],
    "streamUrl": "https://stream1.com"
  }
]
```

#### 7. Get Episodes (Subbed)
```bash
GET /hindi.php?action=getsep&id=12345&token=TOKEN
```
**Returns:** Subbed episode list with URLs

---

## 📡 hindiv2.php API

### Base URL: `/hindiv2.php`
**Source:** AnimixStream.com

### Available Actions:
- `home` - Popular content (20 items)
- `hindi` - Same as home
- `info` - Anime details
- `search` - Search anime (unlimited results)
- `getep` - Get episode list (no URLs)
- `playep` - Get episode URLs

### Endpoints

#### 1. Home Content
```bash
GET /hindiv2.php?action=home&token=TOKEN
```
**Returns:** 20 popular Hindi dubbed anime

#### 2. Hindi Content
```bash
GET /hindiv2.php?action=hindi&token=TOKEN
```
**Returns:** Same as home (20 items)

#### 3. Search Anime
```bash
GET /hindiv2.php?action=search&q=naruto&token=TOKEN
```
**Response Format:**
```json
[
  {
    "id": 456,
    "title": "Naruto Hindi Dubbed",
    "description": "Anime Info: Name: Naruto Language: Hindi Dubbed...",
    "thumbnail": "https://animixstream.com/poster.jpg",
    "type": "dubbed",
    "source": "animixstream.com"
  }
]
```

#### 4. Anime Information
```bash
GET /hindiv2.php?action=info&id=123&token=TOKEN
```
**Response Format:**
```json
{
  "id": 123,
  "title": "Dragon Ball",
  "name": "Dragon Ball",
  "genres": "Action, Adventure",
  "language": "Hindi Dubbed (Official)",
  "quality": "FHD, HD, SD",
  "synopsis": "Story description...",
  "thumbnail": ""
}
```

#### 5. Get Episodes
```bash
GET /hindiv2.php?action=getep&id=123&token=TOKEN
```
**Response Format:**
```json
[
  {
    "episode": "01",
    "title": "Episode 01",
    "id": 123,
    "episode_id": "1"
  }
]
```

#### 6. Play Episode
```bash
GET /hindiv2.php?action=playep&id=123&ep=1&token=TOKEN
```
**Response Format:**
```json
{
  "episode": "01",
  "title": "Episode 01",
  "urls": ["https://strmup.to/embed/...", "https://..."],
  "streamUrl": "https://strmup.to/embed/..."
}
```

---

## 🔄 API Comparison

| Feature | hindi.php | hindiv2.php |
|---------|-----------|-------------|
| **Content Source** | Hinoplex.com | AnimixStream.com |
| **Content Types** | Dubbed + Subbed | Dubbed Only |
| **Home Items** | 25 mixed | 20 dubbed |
| **Search Results** | 25 max | Unlimited |
| **Episode URLs** | Direct in getep | Separate playep |
| **Thumbnails** | High quality | Basic quality |
| **Actions** | 7 actions | 6 actions |

---

## ⚠️ Error Responses

### Authentication Errors
```json
// Missing token
{"error": "Missing token fuck you scraper"}

// Wrong token  
{"error": "Giveing Wrong Token You Stupid Bastard !"}
```

### Invalid Action Errors

**hindi.php:**
```json
{
  "error": "Invalid action provided",
  "message": "Available actions: home, hindi, subbed, info, search, getep, getsep",
  "usage": {
    "home": "api.php?action=home",
    "hindi": "api.php?action=hindi", 
    "subbed": "api.php?action=subbed",
    "info": "api.php?action=info&id=123",
    "search": "api.php?action=search&q=naruto",
    "getep": "api.php?action=getep&id=123",
    "getsep": "api.php?action=getsep&id=123"
  }
}
```

**hindiv2.php:**
```json
{
  "error": "Invalid action provided",
  "message": "Available actions: home, hindi, info, search, getep, playep"
}
```

### Parameter Errors
```json
{"error": "Query parameter required"}
{"error": "ID parameter required"}  
{"error": "ID and ep parameters required"}
```

---

## 🛡️ Anti-Scraping Features

### HTML Disguise Pages
Both APIs show fake HTML pages when accessed without action parameter:

**hindi.php:** Shows "🎌 Anime Database System" loading page
**hindiv2.php:** Shows "🎌 AnimixStream API System" status page

### Security Features
- Token-based authentication
- Input sanitization  
- User-Agent spoofing
- 24-hour file caching
- Rate limiting ready

---

## 🚀 Usage Examples

### Get Popular Content
```bash
# Hindi dubbed + subbed mix
curl "http://localhost:8001/hindi.php?action=home&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"

# Hindi dubbed only
curl "http://localhost:8001/hindiv2.php?action=home&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"
```

### Search Anime
```bash
# Limited results (25 max)
curl "http://localhost:8001/hindi.php?action=search&q=naruto&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"

# Unlimited results
curl "http://localhost:8001/hindiv2.php?action=search&q=naruto&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"
```

### Get Streaming URLs
```bash
# Method 1: hindi.php (direct)
curl "http://localhost:8001/hindi.php?action=getep&id=12345&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"

# Method 2: hindiv2.php (two-step)
curl "http://localhost:8001/hindiv2.php?action=getep&id=123&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"
curl "http://localhost:8001/hindiv2.php?action=playep&id=123&ep=1&token=afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7"
```

---

## 📊 Performance Notes

- **Caching:** 24-hour file caching system
- **Timeout:** 3-15 seconds per request
- **Cache Location:** `/tmp/anime_cache/` & `/tmp/animix_cache/`
- **Fallback URLs:** hindi.php has backup sources
- **Response Format:** JSON with pretty print

---

## 🔧 Technical Details

### Cache System
```php
// Cache key generation
$cache_key = md5($url);

// Cache validity: 24 hours
if (file_exists($file) && (time() - filemtime($file)) < 86400)
```

### Request Headers
```php
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
```

### Response Headers
```php
Content-Type: application/json
Access-Control-Allow-Origin: *
```

---

## 📝 Notes

1. **hindi.php** provides more comprehensive content with both dubbed and subbed
2. **hindiv2.php** focuses on dubbed content with unlimited search
3. Both APIs require the same authentication token
4. Episode URLs are provided differently in each API
5. Error messages include profanity as anti-scraping measure
