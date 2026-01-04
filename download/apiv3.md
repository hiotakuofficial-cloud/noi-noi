# API v3 Usage Examples

## 🔐 **Authentication Required**

**All API endpoints require a valid token parameter.**

**Valid Token:**
```
afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7
```

**Token Methods:**
- **Query Parameter:** `?token=YOUR_TOKEN`
- **POST Body:** `token=YOUR_TOKEN`  
- **Authorization Header:** `Authorization: Bearer YOUR_TOKEN`

**Error Responses:**
```json
// Missing token
{"error": "Missing token fuck you scraper"}

// Invalid token  
{"error": "Giveing Wrong Token You Stupid Bastard !"}
```

---

## 🚀 **Real-World Usage Examples**

### **📱 Mobile App Integration**

#### **1. Home Screen - Popular Anime**
```javascript
// Fetch popular anime for home screen (with token)
const API_TOKEN = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';

fetch(`https://api.hiotaku.com/download/apiv3.php?action=popular&token=${API_TOKEN}`)
  .then(response => response.json())
  .then(data => {
    if (data.error) {
      console.error('API Error:', data.error);
      return;
    }
    data.forEach(anime => {
      console.log(`${anime.title} - ID: ${anime.id}`);
    });
  });
```

#### **2. Anime Detail Screen - Get Episodes**
```javascript
// Get all episodes for an anime (fast loading) with token
const API_TOKEN = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
const animeId = 'frieren-beyond-journeys-end';

fetch(`https://api.hiotaku.com/download/apiv3.php?action=getep&id=${animeId}&token=${API_TOKEN}`)
  .then(response => response.json())
  .then(data => {
    if (data.error) {
      console.error('API Error:', data.error);
      return;
    }
    console.log(`Total Episodes: ${data.total_episodes}`);
    console.log(`Available: ${data.available_episodes}`);
    
    // Display episode list
    data.episodes.forEach(ep => {
      console.log(`Episode ${ep.episode}: ${ep.id}`);
    });
  });
```

#### **3. Video Player - Get Download Links**
```javascript
// Get download links when user clicks play (with token)
const API_TOKEN = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
const episodeId = 'frieren-beyond-journeys-end-episode-1';

fetch(`https://api.hiotaku.com/download/apiv3.php?action=download&ep=${episodeId}&token=${API_TOKEN}`)
  .then(response => response.json())
  .then(data => {
    if (data.error) {
      console.error('API Error:', data.error);
      return;
    }
    if (data.links && data.links.length > 0) {
      // Show quality options
      data.links.forEach(link => {
        console.log(`${link.quality} ${link.type}: ${link.url}`);
      });
    }
  });
```

---

### **🌐 Web Application Examples**

#### **1. Anime Discovery Page**
```php
<?php
// Get trending anime with token
$token = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
$response = file_get_contents("https://api.hiotaku.com/download/apiv3.php?action=trending&token={$token}");
$animes = json_decode($response, true);

if (isset($animes['error'])) {
    die('API Error: ' . $animes['error']);
}

foreach ($animes as $anime) {
    echo "<div class='anime-card'>";
    echo "<img src='{$anime['thumbnail']}' alt='{$anime['title']}'>";
    echo "<h3>{$anime['title']}</h3>";
    echo "<a href='anime.php?id={$anime['id']}'>Watch Now</a>";
    echo "</div>";
}
?>
```

#### **2. Episode List Page**
```php
<?php
$animeId = $_GET['id'];
$response = file_get_contents("https://api.hiotaku.com/download/apiv3.php?action=getep&id={$animeId}");
$data = json_decode($response, true);

echo "<h2>Episodes ({$data['available_episodes']}/{$data['total_episodes']})</h2>";

foreach ($data['episodes'] as $episode) {
    echo "<div class='episode'>";
    echo "<span>Episode {$episode['episode']}</span>";
    echo "<a href='watch.php?ep={$episode['id']}'>Play</a>";
    echo "</div>";
}
?>
```

#### **3. Video Streaming Page**
```php
<?php
$episodeId = $_GET['ep'];
$response = file_get_contents("https://api.hiotaku.com/download/apiv3.php?action=download&ep={$episodeId}");
$data = json_decode($response, true);

if (isset($data['links'])) {
    echo "<video controls>";
    foreach ($data['links'] as $link) {
        if ($link['quality'] == '720p' && $link['type'] == 'Sub') {
            echo "<source src='{$link['url']}' type='video/mp4'>";
            break;
        }
    }
    echo "</video>";
    
    // Quality selector
    echo "<div class='quality-selector'>";
    foreach ($data['links'] as $link) {
        echo "<button onclick='changeQuality(\"{$link['url']}\")'>";
        echo "{$link['quality']} {$link['type']}";
        echo "</button>";
    }
    echo "</div>";
}
?>
```

---

### **🔍 Search & Filter Examples**

#### **1. Search Functionality**
```javascript
// Search anime by name with token
function searchAnime(query) {
    const API_TOKEN = 'afaea552101228848de8f8c7f48a1b7d7a6a042a6094274eaa9d30cb64bf91a7';
    const url = `https://api.hiotaku.com/download/apiv3.php?action=search&q=${encodeURIComponent(query)}&token=${API_TOKEN}`;
    
    fetch(url)
        .then(response => response.json())
        .then(results => {
            if (results.error) {
                console.error('API Error:', results.error);
                return;
            }
            displaySearchResults(results);
        })
        .catch(error => {
            console.error('Network Error:', error);
        });
}

// Usage
searchAnime('naruto');
```

#### **2. Genre Filtering**
```javascript
// Filter by genre
function getAnimeByGenre(genres) {
    const genreString = genres.join(',');
    fetch(`https://api.hiotaku.com/download/apiv3.php?action=genre&genre=${genreString}`)
        .then(response => response.json())
        .then(animes => {
            displayAnimeList(animes);
        });
}

// Usage
getAnimeByGenre(['action', 'adventure']);
```

---

### **📊 Analytics & Monitoring**

#### **1. Performance Monitoring**
```javascript
// Monitor API response times
function monitorApiPerformance() {
    const startTime = performance.now();
    
    fetch('https://api.hiotaku.com/download/apiv3.php?action=popular')
        .then(response => {
            const endTime = performance.now();
            const responseTime = endTime - startTime;
            
            console.log(`API Response Time: ${responseTime.toFixed(2)}ms`);
            
            // Log to analytics
            analytics.track('api_performance', {
                endpoint: 'popular',
                response_time: responseTime,
                cached: responseTime < 100 // Likely cached if < 100ms
            });
        });
}
```

#### **2. Error Handling**
```javascript
// Robust error handling
async function fetchWithRetry(url, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.log(`Attempt ${i + 1} failed:`, error.message);
            
            if (i === maxRetries - 1) {
                throw error;
            }
            
            // Wait before retry
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
        }
    }
}

// Usage
fetchWithRetry('https://api.hiotaku.com/download/apiv3.php?action=popular')
    .then(data => console.log('Success:', data))
    .catch(error => console.error('Failed after retries:', error));
```

---

### **🎮 Advanced Use Cases**

#### **1. Batch Episode Processing**
```javascript
// Download multiple episodes info at once
async function batchGetEpisodes(animeIds) {
    const promises = animeIds.map(id => 
        fetch(`https://api.hiotaku.com/download/apiv3.php?action=getep&id=${id}`)
            .then(response => response.json())
    );
    
    const results = await Promise.all(promises);
    return results;
}

// Usage
const animeList = ['frieren-beyond-journeys-end', 'one-piece', 'naruto'];
batchGetEpisodes(animeList).then(results => {
    results.forEach((data, index) => {
        console.log(`${animeList[index]}: ${data.available_episodes} episodes`);
    });
});
```

#### **2. Smart Caching Strategy**
```javascript
// Client-side caching with localStorage
class AnimeApiCache {
    constructor(ttl = 3600000) { // 1 hour TTL
        this.ttl = ttl;
    }
    
    get(key) {
        const item = localStorage.getItem(`anime_cache_${key}`);
        if (!item) return null;
        
        const data = JSON.parse(item);
        if (Date.now() > data.expiry) {
            localStorage.removeItem(`anime_cache_${key}`);
            return null;
        }
        
        return data.value;
    }
    
    set(key, value) {
        const data = {
            value: value,
            expiry: Date.now() + this.ttl
        };
        localStorage.setItem(`anime_cache_${key}`, JSON.stringify(data));
    }
    
    async fetchWithCache(url, cacheKey) {
        // Check cache first
        const cached = this.get(cacheKey);
        if (cached) {
            console.log('Cache hit:', cacheKey);
            return cached;
        }
        
        // Fetch from API
        const response = await fetch(url);
        const data = await response.json();
        
        // Cache the result
        this.set(cacheKey, data);
        console.log('Cache miss, fetched:', cacheKey);
        
        return data;
    }
}

// Usage
const cache = new AnimeApiCache();
cache.fetchWithCache(
    'https://api.hiotaku.com/download/apiv3.php?action=popular',
    'popular_anime'
).then(data => console.log(data));
```

---

### **📱 React Native Example**

```javascript
// React Native component
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity } from 'react-native';

const AnimeList = () => {
    const [animes, setAnimes] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchPopularAnime();
    }, []);
    
    const fetchPopularAnime = async () => {
        try {
            const response = await fetch('https://api.hiotaku.com/download/apiv3.php?action=popular');
            const data = await response.json();
            setAnimes(data);
        } catch (error) {
            console.error('Error fetching anime:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const renderAnime = ({ item }) => (
        <TouchableOpacity onPress={() => navigateToAnime(item.id)}>
            <View style={styles.animeItem}>
                <Text style={styles.title}>{item.title}</Text>
            </View>
        </TouchableOpacity>
    );
    
    if (loading) {
        return <Text>Loading...</Text>;
    }
    
    return (
        <FlatList
            data={animes}
            renderItem={renderAnime}
            keyExtractor={item => item.id}
        />
    );
};
```

---

### **🔧 Production Tips**

#### **1. Rate Limiting**
```javascript
// Simple rate limiter
class RateLimiter {
    constructor(maxRequests = 10, timeWindow = 60000) {
        this.maxRequests = maxRequests;
        this.timeWindow = timeWindow;
        this.requests = [];
    }
    
    canMakeRequest() {
        const now = Date.now();
        this.requests = this.requests.filter(time => now - time < this.timeWindow);
        
        if (this.requests.length >= this.maxRequests) {
            return false;
        }
        
        this.requests.push(now);
        return true;
    }
}

// Usage
const limiter = new RateLimiter(10, 60000); // 10 requests per minute

function apiRequest(url) {
    if (!limiter.canMakeRequest()) {
        console.log('Rate limit exceeded, please wait');
        return Promise.reject('Rate limited');
    }
    
    return fetch(url);
}
```

#### **2. Environment Configuration**
```javascript
// Environment-based API URLs
const API_CONFIG = {
    development: 'http://localhost:8000/download/apiv3.php',
    staging: 'https://staging-api.hiotaku.com/download/apiv3.php',
    production: 'https://api.hiotaku.com/download/apiv3.php'
};

const API_BASE_URL = API_CONFIG[process.env.NODE_ENV] || API_CONFIG.development;

// Usage
function makeApiRequest(action, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = `${API_BASE_URL}?action=${action}&${queryString}`;
    
    return fetch(url).then(response => response.json());
}
```

---

## 🎯 **Best Practices**

1. **Cache Responses** - Use client-side caching for better performance
2. **Handle Errors** - Always implement proper error handling
3. **Rate Limiting** - Respect API limits to avoid blocking
4. **Batch Requests** - Group multiple requests when possible
5. **Monitor Performance** - Track response times and success rates
6. **Fallback Strategies** - Have backup plans for failed requests

---

## 📞 **Need Help?**

- **GitHub Issues:** [Report Problems](https://github.com/hiotakuofficial-cloud/noi-noi/issues)
- **Documentation:** [Full API Docs](./README.md)
- **Quick Reference:** [Cheat Sheet](./QUICK_REFERENCE.md)
