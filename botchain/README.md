# Hiotaku BotChain API

A fast, secure chat API with AI-powered responses, user authentication, and anime recommendations.

## 🚀 Live API

**Base URL:** `https://v1-w3sc.onrender.com/botchain/api.php`

## 📡 Quick Test

```bash
curl -X POST "https://v1-w3sc.onrender.com/botchain/api.php?token=nehubaby7890" \
  -H "X-User-UUID: mEoyzwKvGgfctvI5h6NeIlnvNQp1" \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello bhai!"}'
```

**Response:**
```json
{
  "success": true,
  "response": "Hi there! I'm Hisu, a model from Hiotaku LLMs. 🤖",
  "user_id": "mEoyzwKvGgfctvI5h6NeIlnvNQp1",
  "user_name": "Bobby Singh",
  "timestamp": 1766625333
}
```

## 🔑 Authentication

### API Token (Required)
```
?token=nehubaby7890
```

### User UUID (Required)
```
X-User-UUID: {firebase-uid-or-uuid}
```

**Supported Formats:**
- Firebase UID: `mEoyzwKvGgfctvI5h6NeIlnvNQp1`
- Standard UUID: `089c767c-16ad-499c-8908-0f880ce54b6b`

## 📋 API Reference

### POST /api.php

**Request:**
```json
{
  "message": "Hello bhai, anime suggest karo!"
}
```

**Headers:**
```
X-User-UUID: mEoyzwKvGgfctvI5h6NeIlnvNQp1
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "response": "Hi There Bobby Singh! I'm Hisu AI Support Of Hiotaku!",
  "user_id": "mEoyzwKvGgfctvI5h6NeIlnvNQp1",
  "user_name": "Bobby Singh",
  "timestamp": 1766625333
}
```

## 🎯 Use Cases

### 1. Mobile App Integration
```kotlin
val request = Request.Builder()
    .url("https://v1-w3sc.onrender.com/botchain/api.php?token=nehubaby7890")
    .addHeader("X-User-UUID", firebaseUser.uid)
    .post(RequestBody.create(
        MediaType.parse("application/json"),
        """{"message": "Hello bhai!"}"""
    ))
    .build()
```

### 2. Web Application
```javascript
fetch('https://v1-w3sc.onrender.com/botchain/api.php?token=nehubaby7890', {
    method: 'POST',
    headers: {
        'X-User-UUID': userUuid,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({message: userMessage})
})
.then(response => response.json())
.then(data => console.log(data.response))
```

### 3. Python Integration
```python
import requests

response = requests.post(
    'https://v1-w3sc.onrender.com/botchain/api.php?token=nehubaby7890',
    headers={
        'X-User-UUID': 'mEoyzwKvGgfctvI5h6NeIlnvNQp1',
        'Content-Type': 'application/json'
    },
    json={'message': 'Hello bhai!'}
)
print(response.json())
```

## ✨ Features

- **AI-Powered Chat** - Natural Hinglish conversations using Mistral AI
- **User Authentication** - Supabase integration with UUID/Firebase UID support
- **Personalized Responses** - Uses real user names from database
- **Chat Memory** - Remembers conversation history per user
- **Anime Recommendations** - Integrated anime search and suggestions
- **Fast Performance** - 6-9 second response times
- **Security** - Token authentication + user validation

## 🎭 Example Conversations

**Greeting:**
```
User: "Hello"
Hisu: "Hi There Bobby Singh! I'm Hisu AI Support Of Hiotaku!"
```

**Name Query:**
```
User: "Mera naam kya hai?"
Hisu: "Aapka naam Bobby Singh hai!"
```

**Anime Request:**
```
User: "Anime suggest karo"
Hisu: "Here are some popular anime:
1. One Piece
2. Naruto
3. Attack on Titan"
```

## ⚡ Performance

- **Response Time:** 6-9 seconds average
- **Caching:** Anime data cached for 1 hour
- **Memory:** File-based chat history for speed
- **Load Balancing:** 3 Mistral API keys for reliability

## 🔒 Security

- **User Validation** - Only registered users in Supabase can access
- **Token Authentication** - API token required for all requests
- **No Data Leakage** - AI doesn't expose sensitive information
- **Privacy Protection** - User data separated from AI context

## 🚨 Error Responses

**Missing Token:**
```json
{
  "error": "Unauthorized",
  "message": "Valid token required. Use ?token=<token> parameter"
}
```

**Missing User ID:**
```json
{
  "error": "User ID required"
}
```

**User Not Found:**
```json
{
  "error": "Access denied",
  "message": "User not found in system. Please register first."
}
```

## 📊 Status

- ✅ **Live:** https://v1-w3sc.onrender.com/botchain/api.php
- ✅ **Response Time:** 6-9 seconds
- ✅ **Uptime:** 99%+
- ✅ **User Database:** Active with real users

## 🤝 Support

For integration support or issues, contact the development team.

---

**Built with ❤️ for Hiotaku Platform**
