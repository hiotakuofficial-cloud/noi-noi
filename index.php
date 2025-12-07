<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiotaku - Premium Anime Streaming App</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="floating-elements"></div>
    
    <div class="dashboard">
        <header class="header">
            <h1 class="logo">HIOTAKU</h1>
            <p class="subtitle">Premium Anime Streaming Experience</p>
        </header>

        <div class="grid-container">
            <!-- App Features Card -->
            <div class="card anime-card">
                <i class="fas fa-play-circle card-icon"></i>
                <h3 class="card-title">Anime Library</h3>
                <p class="card-description">Massive collection of anime series with HD streaming and multiple language options</p>
                <div class="api-status status-active">✅ 10,000+ Episodes</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">1080p</div>
                        <div class="stat-label">HD Quality</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Streaming</div>
                    </div>
                </div>
            </div>

            <!-- App Updates Card -->
            <div class="card app-card">
                <i class="fas fa-mobile-alt card-icon"></i>
                <h3 class="card-title">Mobile App</h3>
                <p class="card-description">Download our Android app for seamless anime streaming on your mobile device</p>
                <div class="api-status status-active">✅ Latest Version 2.1</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">4.8</div>
                        <div class="stat-label">Rating</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">50K+</div>
                        <div class="stat-label">Downloads</div>
                    </div>
                </div>
            </div>

            <!-- Content Features Card -->
            <div class="card youtube-card">
                <i class="fas fa-download card-icon"></i>
                <h3 class="card-title">Offline Viewing</h3>
                <p class="card-description">Download episodes for offline viewing with multiple quality options</p>
                <div class="api-status status-active">✅ Premium Feature</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">720p</div>
                        <div class="stat-label">Min Quality</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">Unlimited</div>
                        <div class="stat-label">Downloads</div>
                    </div>
                </div>
            </div>

            <!-- Social Features Card -->
            <div class="card pinterest-card">
                <i class="fas fa-users card-icon"></i>
                <h3 class="card-title">Community</h3>
                <p class="card-description">Join our anime community, share reviews and discover new series</p>
                <div class="api-status status-active">✅ Active Community</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">25K+</div>
                        <div class="stat-label">Members</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">Daily</div>
                        <div class="stat-label">Updates</div>
                    </div>
                </div>
            </div>

            <!-- Language Support Card -->
            <div class="card hindi-card">
                <i class="fas fa-language card-icon"></i>
                <h3 class="card-title">Multi-Language</h3>
                <p class="card-description">Watch anime in Hindi, English, Japanese with subtitles and dubbing options</p>
                <div class="api-status status-active">✅ 5+ Languages</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">Hindi</div>
                        <div class="stat-label">Dubbed</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">Multi</div>
                        <div class="stat-label">Subs</div>
                    </div>
                </div>
            </div>

            <!-- About App Card -->
            <div class="card stats-card">
                <i class="fas fa-info-circle card-icon"></i>
                <h3 class="card-title">About Hiotaku</h3>
                <p class="card-description">Your ultimate destination for anime streaming with premium features and ad-free experience</p>
                <div class="api-status status-active">✅ Premium Service</div>
                <div class="card-stats">
                    <div class="stat">
                        <div class="stat-number">2024</div>
                        <div class="stat-label">Launched</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">Ad-Free</div>
                        <div class="stat-label">Experience</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Information Section -->
        <div style="margin-top: 60px; text-align: center;">
            <h2 style="color: #00d4ff; font-family: 'Orbitron', monospace; margin-bottom: 20px;">Download Hiotaku App</h2>
            <div style="background: rgba(255,255,255,0.05); padding: 30px; border-radius: 15px; max-width: 800px; margin: 0 auto;">
                <p style="color: #8892b0; font-size: 1.1rem; line-height: 1.8;">
                    Experience the best anime streaming with our mobile app. Watch thousands of episodes in HD quality, 
                    download for offline viewing, and enjoy an ad-free premium experience.
                </p>
                <div style="margin-top: 20px;">
                    <button onclick="window.open('https://hiotaku.kesug.com', '_blank')" 
                            style="background: linear-gradient(45deg, #00d4ff, #ff0080); border: none; padding: 12px 30px; border-radius: 25px; color: white; font-weight: 600; cursor: pointer; margin: 0 10px;">
                        📱 Download App
                    </button>
                    <button onclick="window.open('https://hiotaku.kesug.com/about', '_blank')" 
                            style="background: linear-gradient(45deg, #ff6b6b, #ffa500); border: none; padding: 12px 30px; border-radius: 25px; color: white; font-weight: 600; cursor: pointer; margin: 0 10px;">
                        ℹ️ Learn More
                    </button>
                </div>
            </div>
        </div>

        <!-- Features List -->
        <div style="margin-top: 40px; max-width: 1000px; margin-left: auto; margin-right: auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-shield-alt" style="font-size: 2rem; color: #2ecc71; margin-bottom: 10px;"></i>
                    <h4 style="color: #ffffff; margin-bottom: 10px;">Secure Streaming</h4>
                    <p style="color: #8892b0; font-size: 0.9rem;">Safe and secure anime streaming platform</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-rocket" style="font-size: 2rem; color: #e74c3c; margin-bottom: 10px;"></i>
                    <h4 style="color: #ffffff; margin-bottom: 10px;">Fast Loading</h4>
                    <p style="color: #8892b0; font-size: 0.9rem;">Lightning fast episode loading and streaming</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-heart" style="font-size: 2rem; color: #f39c12; margin-bottom: 10px;"></i>
                    <h4 style="color: #ffffff; margin-bottom: 10px;">Favorites</h4>
                    <p style="color: #8892b0; font-size: 0.9rem;">Save your favorite anime and track progress</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-bell" style="font-size: 2rem; color: #9b59b6; margin-bottom: 10px;"></i>
                    <h4 style="color: #ffffff; margin-bottom: 10px;">Notifications</h4>
                    <p style="color: #8892b0; font-size: 0.9rem;">Get notified about new episodes and updates</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>
