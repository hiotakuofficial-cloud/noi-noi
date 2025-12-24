<?php
require_once __DIR__ . '/../core/ai.php';
require_once __DIR__ . '/../core/anime.php';

class Bot {
    private $ai;
    private $anime;
    
    public function __construct() {
        $this->ai = new AI();
        $this->anime = new AnimeAPI();
    }
    
    public function processWithContext($message, $conversationContext = '') {
        // Check cache first
        $cacheKey = md5(strtolower(trim($message)) . $conversationContext);
        $cacheFile = '/tmp/hiotaku_cache_' . $cacheKey;
        
        // Skip cache for personalized conversations with context
        if(empty($conversationContext) && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            file_put_contents('/tmp/hiotaku_debug.log', date('Y-m-d H:i:s') . " - Cache hit for: " . $message . "\n", FILE_APPEND);
            return file_get_contents($cacheFile);
        }
        
        // Get anime data with caching
        $animeFile = '/tmp/hiotaku_anime_' . date('H');
        if(file_exists($animeFile) && (time() - filemtime($animeFile)) < 3600) {
            $popularAnime = json_decode(file_get_contents($animeFile), true);
        } else {
            $popularAnime = $this->anime->search('popular');
            file_put_contents($animeFile, json_encode($popularAnime));
        }
        
        // Enhanced prompt with conversation context
        $smartPrompt = "You are Hisu from Hiotaku LLMs Model v1. 

$conversationContext

Current user message: '$message'

INSTRUCTIONS:
- Remember previous conversation context and respond naturally
- If user wants anime suggestions/recommendations: Show numbered list from available anime below
- If user asks about specific anime availability: Check if it exists in available anime and respond accordingly  
- If normal conversation: Respond naturally as Hisu, referencing previous context when relevant

AVAILABLE ANIME ON HIOTAKU:
" . json_encode($popularAnime) . "

RESPONSE RULES:
- Use Hinglish if user uses Hindi words (hai, me, bhai, yaar, kya, etc.)
- Use English GenZ slang otherwise
- Be cool, friendly, short responses
- Use emojis naturally when they add value
- For availability: If anime found in list above, say it's available. If not found, say not available.
- Reference previous conversation naturally when relevant

Respond directly as Hisu:";

        file_put_contents('/tmp/hiotaku_debug.log', date('Y-m-d H:i:s') . " - Context call for: " . $message . "\n", FILE_APPEND);
        
        $response = $this->ai->chat($smartPrompt);
        
        // Only cache responses without personal context
        if(empty($conversationContext)) {
            file_put_contents($cacheFile, $response);
        }
        
        return $response;
    }
    
    public function processWithUserContext($message, $conversationContext = '', $userName = 'User') {
        // Check cache first (skip for personalized conversations)
        $cacheKey = md5(strtolower(trim($message)) . $conversationContext . $userName);
        $cacheFile = '/tmp/hiotaku_cache_' . $cacheKey;
        
        // Skip cache for personalized conversations
        if(empty($conversationContext) && $userName === 'User' && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            file_put_contents('/tmp/hiotaku_debug.log', date('Y-m-d H:i:s') . " - Cache hit for: " . $message . "\n", FILE_APPEND);
            return file_get_contents($cacheFile);
        }
        
        // Get anime data with caching
        $animeFile = '/tmp/hiotaku_anime_' . date('H');
        if(file_exists($animeFile) && (time() - filemtime($animeFile)) < 3600) {
            $popularAnime = json_decode(file_get_contents($animeFile), true);
        } else {
            $popularAnime = $this->anime->search('popular');
            file_put_contents($animeFile, json_encode($popularAnime));
        }
        
        // Enhanced prompt with user name and conversation context
        $smartPrompt = "You are Hisu AI Support from Hiotaku. 

The user's name is: $userName

$conversationContext

User message: '$message'

GREETING RESPONSES (for Hello, Hi, Hey):
You MUST respond EXACTLY like this: 'Hi There $userName! I'm Hisu AI Support Of Hiotaku!'

OTHER RESPONSES:
- Name questions: 'Your name is $userName' or 'Aapka naam $userName hai'
- Anime requests: Show numbered list from available anime
- Normal chat: Be friendly and helpful

AVAILABLE ANIME:
" . json_encode($popularAnime) . "

Remember: For ANY greeting (Hello/Hi/Hey), use EXACTLY: 'Hi There $userName! I'm Hisu AI Support Of Hiotaku!'

Respond as Hisu:";

        file_put_contents('/tmp/hiotaku_debug.log', date('Y-m-d H:i:s') . " - User context call for: " . $message . " (User: $userName)\n", FILE_APPEND);
        
        $response = $this->ai->chat($smartPrompt);
        
        // Only cache non-personalized responses
        if(empty($conversationContext) && $userName === 'User') {
            file_put_contents($cacheFile, $response);
        }
        
        return $response;
    }
}
?>
