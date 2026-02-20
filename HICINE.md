# Hicine API Proxy

A secure PHP-based proxy API for accessing Hicine movie/series streaming data with token authentication and input validation.

## 🚀 Features

- ✅ Token-based authentication
- ✅ 12+ API endpoints
- ✅ Input validation & sanitization
- ✅ Pretty JSON output
- ✅ CORS enabled
- ✅ Error handling with timeouts
- ✅ Pagination support
- ✅ Rate limit protection via token

## 📋 Requirements

- PHP 8.0+
- `file_get_contents` enabled
- `allow_url_fopen = On`

## 🔐 Authentication

All requests require a valid API token passed via:

**Query Parameter:**
```
?token=YOUR_TOKEN
```

**Header:**
```
Authorization: Bearer YOUR_TOKEN
```

**POST Body:**
```
token=YOUR_TOKEN
```

### Default Token
```
afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7
```

## 📡 API Endpoints

### Base URL
```
http://your-domain.com/hicine.php
```

---

## 1. Search Content

Search for movies, series, or anime.

**Endpoint:** `?action=search`

**Parameters:**
- `q` (required) - Search query
- `token` (required) - API token

**Example:**
```bash
curl "http://localhost/hicine.php?action=search&q=avengers&token=YOUR_TOKEN"
```

**Response:**
```json
[
  {
    "_id": "69984f36e150c84e4910e103",
    "title": "Avengers Grimm: Time Wars (2018)",
    "featured_image": "https://storage.hicine.info/images/...",
    "links": "download_links...",
    "categories": "Hollywood,2018,480p,720p,Action",
    "contentType": "movies"
  }
]
```

---

## 2. Recent Content

Get recently added content.

**Endpoint:** `?action=recent`

**Parameters:**
- `type` (optional) - Content type: `all`, `movies`, `series`, `anime` (default: `all`)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=recent&type=movies&token=YOUR_TOKEN"
```

---

## 3. Trending Content

Get trending movies and series.

**Endpoint:** `?action=trending`

**Parameters:**
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=trending&token=YOUR_TOKEN"
```

---

## 4. Movie Details

Get detailed information about a specific movie.

**Endpoint:** `?action=movie`

**Parameters:**
- `id` (required) - Movie MongoDB ID
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=movie&id=69984f36e150c84e4910dae0&token=YOUR_TOKEN"
```

**Response:**
```json
{
  "_id": "69984f36e150c84e4910dae0",
  "title": "Hijack 1971 (2024)",
  "featured_image": "https://storage.hicine.info/images/...",
  "links": "480p_link, 720p_link, 1080p_link",
  "categories": "Hollywood,2024,Action,Thriller"
}
```

---

## 5. Series Details

Get series information with all seasons and episodes.

**Endpoint:** `?action=series`

**Parameters:**
- `id` (required) - Series MongoDB ID
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=series&id=69984f40e150c84e491100ff&token=YOUR_TOKEN"
```

**Response:**
```json
{
  "_id": "69984f40e150c84e491100ff",
  "title": "The Night Agent | NetFlix Series",
  "season_1": "Episode 1: link, Episode 2: link...",
  "season_2": "Episode 1: link, Episode 2: link...",
  "season_3": "Episode 1: link, Episode 2: link..."
}
```

**Note:** Automatically tries `hollywood_series`, `bollywood_series`, and `anime` endpoints.

---

## 6. Anime Details

Get anime series details.

**Endpoint:** `?action=anime`

**Parameters:**
- `id` (required) - Anime MongoDB ID
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=anime&id=69984f46e150c84e491107d4&token=YOUR_TOKEN"
```

---

## 7. Anime List

Get paginated list of anime series.

**Endpoint:** `?action=anime_list`

**Parameters:**
- `limit` (optional) - Results per page (1-100, default: 50)
- `offset` (optional) - Pagination offset (default: 0)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=anime_list&limit=10&offset=0&token=YOUR_TOKEN"
```

---

## 8. Bollywood Movies

Get Bollywood movies list or specific movie details.

**Endpoint:** `?action=bollywood_movies`

**Parameters:**
- `id` (optional) - Movie ID for specific movie
- `limit` (optional) - Results per page (1-100, default: 50)
- `offset` (optional) - Pagination offset (default: 0)
- `token` (required)

**List Example:**
```bash
curl "http://localhost/hicine.php?action=bollywood_movies&limit=20&token=YOUR_TOKEN"
```

**Single Movie:**
```bash
curl "http://localhost/hicine.php?action=bollywood_movies&id=MOVIE_ID&token=YOUR_TOKEN"
```

---

## 9. Bollywood Series

Get Bollywood series list or specific series details.

**Endpoint:** `?action=bollywood_series`

**Parameters:**
- `id` (optional) - Series ID for specific series
- `limit` (optional) - Results per page (1-100, default: 50)
- `offset` (optional) - Pagination offset (default: 0)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=bollywood_series&limit=10&token=YOUR_TOKEN"
```

---

## 10. Hollywood Movies

Get Hollywood movies list.

**Endpoint:** `?action=hollywood_movies`

**Parameters:**
- `limit` (optional) - Results per page (1-100, default: 50)
- `offset` (optional) - Pagination offset (default: 0)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=hollywood_movies&limit=25&offset=0&token=YOUR_TOKEN"
```

---

## 11. Hollywood Series

Get Hollywood series list.

**Endpoint:** `?action=hollywood_series`

**Parameters:**
- `limit` (optional) - Results per page (1-100, default: 50)
- `offset` (optional) - Pagination offset (default: 0)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=hollywood_series&limit=15&token=YOUR_TOKEN"
```

---

## 12. Platform Content

Get content by streaming platform (Netflix, Disney+, etc.).

**Endpoint:** `?action=platform`

**Parameters:**
- `platform` (required) - Platform name (e.g., `netflix`, `disney`, `hbo`)
- `type` (optional) - Content type: `all`, `movies`, `series` (default: `all`)
- `token` (required)

**Example:**
```bash
curl "http://localhost/hicine.php?action=platform&platform=netflix&type=series&token=YOUR_TOKEN"
```

**Note:** This endpoint may return errors as the collection is not available on Hicine API.

---

## 🔒 Security Features

### Input Validation

All inputs are sanitized:

- **`id`**: Only alphanumeric, underscore, hyphen allowed
- **`type`**: Only lowercase letters and underscore
- **`platform`**: Only lowercase letters and underscore
- **`limit`**: Integer between 1-100
- **`offset`**: Non-negative integer
- **`query`**: URL encoded automatically

### Error Handling

- Network timeout: 30 seconds
- JSON validation
- HTTP error handling
- Null response checks

---

## 📊 Response Format

### Success Response
```json
{
  "_id": "unique_id",
  "title": "Content Title",
  "featured_image": "image_url",
  "links": "download_links",
  "categories": "category,tags",
  "contentType": "movies|series|anime"
}
```

### Error Response
```json
{
  "error": "Error message"
}
```

**HTTP Status Codes:**
- `200` - Success
- `401` - Unauthorized (invalid/missing token)
- `500` - Server error (failed to fetch data)

---

## 🛠️ Installation

1. **Upload files:**
   ```bash
   - hicine.php
   - auth.php
   ```

2. **Configure auth.php:**
   ```php
   $validToken = 'your-secure-token-here';
   ```

3. **Test endpoint:**
   ```bash
   curl "http://your-domain.com/hicine.php?action=trending&token=YOUR_TOKEN"
   ```

---

## 📝 Usage Examples

### JavaScript (Fetch API)
```javascript
const token = 'YOUR_TOKEN';
const action = 'search';
const query = 'avengers';

fetch(`http://your-domain.com/hicine.php?action=${action}&q=${query}&token=${token}`)
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(err => console.error(err));
```

### Python (Requests)
```python
import requests

url = "http://your-domain.com/hicine.php"
params = {
    "action": "trending",
    "token": "YOUR_TOKEN"
}

response = requests.get(url, params=params)
print(response.json())
```

### PHP (cURL)
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://your-domain.com/hicine.php?action=recent&token=YOUR_TOKEN");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
```

---

## ⚠️ Important Notes

1. **Token Security**: Keep your API token secure. Don't expose it in client-side code.
2. **Rate Limiting**: Token-based authentication prevents abuse.
3. **Pagination**: Use `limit` and `offset` for large datasets.
4. **Caching**: Consider implementing caching for frequently accessed data.
5. **HTTPS**: Use HTTPS in production for secure communication.

---

## 🐛 Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| `Missing token fuck you scraper` | No token provided | Add `token` parameter |
| `Giveing Wrong Token You Stupid Bastard !` | Invalid token | Use correct token |
| `Query parameter required` | Missing search query | Add `q` parameter |
| `ID parameter required` | Missing ID for movie/series | Add `id` parameter |
| `Platform parameter required` | Missing platform name | Add `platform` parameter |
| `Invalid action` | Unknown action | Use valid action name |
| `Failed to fetch data from Hicine API` | Network/API error | Check Hicine API status |

---

## 📦 Response Data Structure

### Download Links Format
```
https://worker.dev/?vcloud=https://vcloud.zip/FILE_ID, Link2, Link3, Title [Quality], Size
```

### Categories Format
```
Category1,Category2,Year,Quality1,Quality2,Genre1,Genre2
```

### Season Data Format (Series)
```
Season Title
Episode 1 : link, size, quality : link, size, quality
Episode 2 : link, size, quality : link, size, quality
```

---

## 🔄 API Workflow

```
Client Request
    ↓
Token Verification (auth.php)
    ↓
Input Validation & Sanitization
    ↓
Route to Appropriate Endpoint
    ↓
Fetch from Hicine API
    ↓
JSON Decode & Validate
    ↓
Return Pretty JSON Response
```

---

## 📈 Performance

- **Timeout**: 30 seconds per request
- **Max Limit**: 100 items per request
- **Response Format**: Pretty JSON with unescaped slashes
- **CORS**: Enabled for all origins

---

## 🤝 Contributing

To add new endpoints or improve functionality:

1. Add new case in switch statement
2. Implement input validation
3. Add error handling
4. Update this documentation

---

## 📄 License

This is a proxy API for educational purposes. Respect Hicine's terms of service.

---

## 🔗 Related Files

- `hicine.php` - Main API file
- `auth.php` - Authentication handler
- `HICINE.md` - This documentation

---

## 📞 Support

For issues or questions:
- Check error messages
- Verify token validity
- Ensure Hicine API is accessible
- Review input parameters

---

**Last Updated:** 2026-02-20  
**Version:** 1.0.0  
**Status:** Production Ready ✅
