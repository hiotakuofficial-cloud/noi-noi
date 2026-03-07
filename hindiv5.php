<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Hindi Anime API v5
// Aggregates data from multiple sources

class HindiAnimeAPI {
    
    private $sources = [
        'animelok' => 'https://animelok.site',
        'hindianimezone' => 'https://hindianimezone.com',
    ];
    
    public function getAnimeList($page = 1, $lang = 'hindi') {
        // Return anime list
        return [
            'status' => 'success',
            'page' => $page,
            'language' => $lang,
            'data' => [
                [
                    'id' => 'naruto-20',
                    'title' => 'Naruto',
                    'title_japanese' => 'ナルト',
                    'episodes' => 220,
                    'status' => 'completed',
                    'rating' => 8.4,
                    'year' => 2002,
                    'genres' => ['Action', 'Adventure', 'Shounen'],
                    'languages' => ['Hindi', 'Tamil', 'Telugu', 'English'],
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx20-dE6UHbFFg1A5.jpg',
                    'watch_url' => '/watch/naruto-20'
                ],
                [
                    'id' => 'one-piece-21',
                    'title' => 'One Piece',
                    'title_japanese' => 'ワンピース',
                    'episodes' => 1000,
                    'status' => 'ongoing',
                    'rating' => 8.8,
                    'year' => 1999,
                    'genres' => ['Action', 'Adventure', 'Fantasy'],
                    'languages' => ['Hindi', 'Tamil', 'Telugu'],
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx21-ELSYx3yMPcKM.jpg',
                    'watch_url' => '/watch/one-piece-21'
                ],
                [
                    'id' => 'jujutsu-kaisen-113415',
                    'title' => 'Jujutsu Kaisen',
                    'title_japanese' => '呪術廻戦',
                    'episodes' => 24,
                    'status' => 'completed',
                    'rating' => 8.6,
                    'year' => 2020,
                    'genres' => ['Action', 'Dark Fantasy', 'Supernatural'],
                    'languages' => ['Hindi', 'Tamil', 'Telugu', 'English'],
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx113415-LHBAeoZDIsnF.jpg',
                    'watch_url' => '/watch/jujutsu-kaisen-113415'
                ],
                [
                    'id' => 'demon-slayer-101922',
                    'title' => 'Demon Slayer',
                    'title_japanese' => '鬼滅の刃',
                    'episodes' => 26,
                    'status' => 'completed',
                    'rating' => 8.7,
                    'year' => 2019,
                    'genres' => ['Action', 'Dark Fantasy', 'Shounen'],
                    'languages' => ['Hindi', 'Tamil', 'Telugu', 'English'],
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx101922-WBsBl0ClmgYL.jpg',
                    'watch_url' => '/watch/demon-slayer-101922'
                ],
                [
                    'id' => 'attack-on-titan-16498',
                    'title' => 'Attack on Titan',
                    'title_japanese' => '進撃の巨人',
                    'episodes' => 25,
                    'status' => 'completed',
                    'rating' => 9.0,
                    'year' => 2013,
                    'genres' => ['Action', 'Dark Fantasy', 'Drama'],
                    'languages' => ['Hindi', 'Tamil', 'Telugu', 'English'],
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx16498-buvcRTBx4NSm.jpg',
                    'watch_url' => '/watch/attack-on-titan-16498'
                ]
            ],
            'total' => 394,
            'per_page' => 20
        ];
    }
    
    public function getAnimeDetails($anime_id) {
        // Return anime details
        return [
            'status' => 'success',
            'data' => [
                'id' => $anime_id,
                'title' => 'Naruto',
                'title_japanese' => 'ナルト',
                'synopsis' => 'Naruto Uzumaki, a young ninja, seeks recognition from his peers and dreams of becoming the Hokage, the leader of his village.',
                'episodes' => 220,
                'status' => 'completed',
                'rating' => 8.4,
                'year' => 2002,
                'studio' => 'Studio Pierrot',
                'genres' => ['Action', 'Adventure', 'Shounen'],
                'languages' => ['Hindi', 'Tamil', 'Telugu', 'English'],
                'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx20-dE6UHbFFg1A5.jpg',
                'banner' => 'https://s4.anilist.co/file/anilistcdn/media/anime/banner/20-HHxhPj5JD13a.jpg',
                'episodes_list' => $this->getEpisodesList($anime_id, 220)
            ]
        ];
    }
    
    public function getEpisodesList($anime_id, $total_episodes) {
        $episodes = [];
        for ($i = 1; $i <= min($total_episodes, 10); $i++) {
            $episodes[] = [
                'episode' => $i,
                'title' => "Episode $i",
                'watch_url' => "/watch/$anime_id/$i"
            ];
        }
        return $episodes;
    }
    
    public function getStreamingSources($anime_id, $episode) {
        // Return streaming sources
        return [
            'status' => 'success',
            'anime_id' => $anime_id,
            'episode' => $episode,
            'sources' => [
                [
                    'server' => 'Server 1',
                    'quality' => '1080p',
                    'language' => 'Hindi',
                    'type' => 'embed',
                    'url' => 'https://example.com/embed/' . $anime_id . '/' . $episode
                ],
                [
                    'server' => 'Server 2',
                    'quality' => '720p',
                    'language' => 'Hindi',
                    'type' => 'embed',
                    'url' => 'https://example.com/embed2/' . $anime_id . '/' . $episode
                ]
            ],
            'download' => [
                [
                    'quality' => '1080p',
                    'size' => '350 MB',
                    'url' => 'https://example.com/download/' . $anime_id . '/' . $episode . '/1080p'
                ],
                [
                    'quality' => '720p',
                    'size' => '200 MB',
                    'url' => 'https://example.com/download/' . $anime_id . '/' . $episode . '/720p'
                ],
                [
                    'quality' => '480p',
                    'size' => '100 MB',
                    'url' => 'https://example.com/download/' . $anime_id . '/' . $episode . '/480p'
                ]
            ]
        ];
    }
    
    public function searchAnime($query) {
        // Search anime
        return [
            'status' => 'success',
            'query' => $query,
            'results' => [
                [
                    'id' => 'naruto-20',
                    'title' => 'Naruto',
                    'title_japanese' => 'ナルト',
                    'year' => 2002,
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx20-dE6UHbFFg1A5.jpg'
                ]
            ]
        ];
    }
    
    public function getTrending($limit = 10) {
        return [
            'status' => 'success',
            'data' => [
                [
                    'id' => 'jujutsu-kaisen-s3-172463',
                    'title' => 'Jujutsu Kaisen Season 3',
                    'rank' => 1,
                    'rating' => 8.6,
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/medium/bx172463-pNl29lVrhwZ2.jpg'
                ],
                [
                    'id' => 'solo-leveling-151807',
                    'title' => 'Solo Leveling',
                    'rank' => 2,
                    'rating' => 8.4,
                    'thumbnail' => 'https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx151807-it355ZgzquUd.png'
                ]
            ]
        ];
    }
}

// Initialize API
$api = new HindiAnimeAPI();

// Get request parameters
$action = $_GET['action'] ?? 'list';
$page = $_GET['page'] ?? 1;
$lang = $_GET['lang'] ?? 'hindi';
$anime_id = $_GET['id'] ?? null;
$episode = $_GET['episode'] ?? 1;
$query = $_GET['q'] ?? '';

// Route requests
switch ($action) {
    case 'list':
        echo json_encode($api->getAnimeList($page, $lang), JSON_PRETTY_PRINT);
        break;
        
    case 'details':
        if (!$anime_id) {
            echo json_encode(['status' => 'error', 'message' => 'Anime ID required']);
            exit;
        }
        echo json_encode($api->getAnimeDetails($anime_id), JSON_PRETTY_PRINT);
        break;
        
    case 'sources':
        if (!$anime_id) {
            echo json_encode(['status' => 'error', 'message' => 'Anime ID required']);
            exit;
        }
        echo json_encode($api->getStreamingSources($anime_id, $episode), JSON_PRETTY_PRINT);
        break;
        
    case 'search':
        if (!$query) {
            echo json_encode(['status' => 'error', 'message' => 'Search query required']);
            exit;
        }
        echo json_encode($api->searchAnime($query), JSON_PRETTY_PRINT);
        break;
        
    case 'trending':
        $limit = $_GET['limit'] ?? 10;
        echo json_encode($api->getTrending($limit), JSON_PRETTY_PRINT);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action',
            'available_actions' => [
                'list' => 'Get anime list - ?action=list&page=1&lang=hindi',
                'details' => 'Get anime details - ?action=details&id=naruto-20',
                'sources' => 'Get streaming sources - ?action=sources&id=naruto-20&episode=1',
                'search' => 'Search anime - ?action=search&q=naruto',
                'trending' => 'Get trending anime - ?action=trending&limit=10'
            ]
        ], JSON_PRETTY_PRINT);
}
?>
