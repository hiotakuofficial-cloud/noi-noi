<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Launch date: 17 Feb 2026, 00:00 IST
$launchDate = strtotime('2026-02-17 00:00:00');
$currentDate = time();

// Check if current date is past launch date
if ($currentDate >= $launchDate) {
    header('Location: https://hiotaku.kesug.com');
    exit();
}

// Calculate time remaining
$timeLeft = $launchDate - $currentDate;
$days = floor($timeLeft / (60 * 60 * 24));
$hours = floor(($timeLeft % (60 * 60 * 24)) / (60 * 60));
$minutes = floor(($timeLeft % (60 * 60)) / 60);
$seconds = $timeLeft % 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiotaku - Free Anime Streaming Platform India | Watch Anime Online Free</title>
    <meta name="description" content="Hiotaku is India's free anime streaming platform launching February 17, 2026. Watch anime online free with HD quality, curated content, and ad-free episodes.">
    <meta name="keywords" content="watch anime online free, demon slayer season 4, one piece latest episodes, attack on titan final season, jujutsu kaisen season 3, chainsaw man anime, spy x family, mob psycho 100, tokyo revengers, my hero academia, naruto shippuden, dragon ball super, anime hindi dub, anime english sub, latest anime 2026, trending anime, popular anime series, anime streaming site, free anime website, hd anime episodes">
    <meta name="robots" content="index, follow">
    <meta name="author" content="NEHU SINGH">
    <meta property="og:title" content="Hiotaku - Free Anime Streaming Platform India">
    <meta property="og:description" content="Experience anime the right way with Hiotaku - India's free anime streaming platform launching February 2026. Watch anime online free with HD quality.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hiotaku.kesug.com">
    <meta property="og:image" content="assets/image/logo.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Hiotaku - Free Anime Streaming Platform India">
    <meta name="twitter:description" content="Watch anime online free with Hiotaku - launching February 2026 with curated content and HD quality.">
    <link rel="canonical" href="https://hiotaku.kesug.com">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml">
    <link rel="icon" type="image/png" href="assets/favicon/favicon.png">
    <script src="https://pl28446895.effectivegatecpm.com/65/fe/9b/65fe9bdd10245701eb618180da1837e5.js"></script>
    <script>
      atOptions = {
        'key' : '780ebea0e28f410fed83762813132e04',
        'format' : 'iframe',
        'height' : 50,
        'width' : 320,
        'params' : {}
      };
    </script>
    <script src="https://www.highperformanceformat.com/780ebea0e28f410fed83762813132e04/invoke.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #121212;
            color: #ffffff;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            animation: fadeIn 1.5s ease-out;
        }

        .logo {
            max-width: 300px;
            height: auto;
            margin-bottom: 1rem;
            animation: slideUp 1s ease-out 0.3s both;
        }

        .tagline {
            font-size: 1.5rem;
            font-weight: 300;
            color: #cccccc;
            margin-bottom: 3rem;
            animation: slideUp 1s ease-out 0.6s both;
        }

        .countdown-container {
            margin-bottom: 3rem;
            animation: slideUp 1s ease-out 0.9s both;
        }

        .countdown {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .time-unit {
            background: rgba(255, 140, 0, 0.1);
            border: 2px solid rgba(255, 140, 0, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            min-width: 120px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .time-unit:hover {
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.3);
        }

        .time-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ff8c00;
            display: block;
        }

        .time-label {
            font-size: 0.9rem;
            color: #cccccc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .countdown-note {
            font-size: 0.85rem;
            color: #888888;
            font-style: italic;
        }

        .cta-button {
            background: linear-gradient(45deg, #ff8c00, #ffa500);
            color: #ffffff;
            border: none;
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: slideUp 1s ease-out 1.2s both;
            text-decoration: none;
            display: inline-block;
        }
        
        .cta-button.disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .cta-button:not(.disabled):hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.5);
        }

        .footer {
            text-align: center;
            padding: 2rem;
            color: #666666;
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-links {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: #cccccc;
            text-decoration: none;
            margin: 0 1rem;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #ff8c00;
        }

        .about-section {
            background: #121212;
            padding: 4rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .about-content {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .about-content h2 {
            font-size: 2.5rem;
            color: #ff8c00;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .about-content > p {
            font-size: 1.2rem;
            color: #cccccc;
            line-height: 1.6;
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 140, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 140, 0, 0.4);
        }

        .feature h3 {
            font-size: 1.3rem;
            color: #ff8c00;
            margin-bottom: 1rem;
        }

        .feature p {
            color: #cccccc;
            line-height: 1.5;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .logo {
                font-size: 2.5rem;
            }
            
            .tagline {
                font-size: 1.2rem;
            }
            
            .countdown {
                gap: 1rem;
            }
            
            .time-unit {
                min-width: 80px;
                padding: 1rem;
            }
            
            .time-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <img src="assets/image/logo.png" alt="Hiotaku" class="logo">
        <p class="tagline">Experience Anime the Right Way</p>
        
        <div class="countdown-container">
            <div class="countdown">
                <div class="time-unit">
                    <span class="time-number" id="days"><?php echo sprintf('%02d', $days); ?></span>
                    <span class="time-label">Days</span>
                </div>
                <div class="time-unit">
                    <span class="time-number" id="hours"><?php echo sprintf('%02d', $hours); ?></span>
                    <span class="time-label">Hours</span>
                </div>
                <div class="time-unit">
                    <span class="time-number" id="minutes"><?php echo sprintf('%02d', $minutes); ?></span>
                    <span class="time-label">Minutes</span>
                </div>
                <div class="time-unit">
                    <span class="time-number" id="seconds"><?php echo sprintf('%02d', $seconds); ?></span>
                    <span class="time-label">Seconds</span>
                </div>
            </div>
            <p class="countdown-note">When the countdown ends, Hiotaku will be live.</p>
        </div>
        
        <button class="cta-button disabled" id="ctaButton">Launching Soon</button>
    </div>
    
    <div class="about-section">
        <div class="about-content">
            <h2>What is Hiotaku?</h2>
            <p>Hiotaku is India's premium anime streaming platform, bringing you the latest and greatest anime series with high-quality subtitles and dubbing. Experience your favorite shows in HD with zero ads during episodes.</p>
            
            <div class="features">
                <div class="feature">
                    <h3>Curated Content</h3>
                    <p>Hand-picked anime series from top studios worldwide</p>
                </div>
                <div class="feature">
                    <h3>Lightning Fast</h3>
                    <p>Ultra-fast streaming with adaptive quality technology</p>
                </div>
                <div class="feature">
                    <h3>Premium Experience</h3>
                    <p>Ad-free viewing with offline downloads available</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-links">
            <a href="privacy.html">Privacy Policy</a>
            <a href="terms.html">Terms of Service</a>
            <a href="contact.html">Contact Us</a>
            <a href="about.html">About</a>
        </div>
        <p>&copy; Hiotaku 2026 | All Rights Reserved</p>
    </footer>

    <script>
        // Live countdown update using JavaScript
        const launchDate = new Date('2026-02-17T00:00:00+05:30');
        const ctaButton = document.getElementById('ctaButton');
        
        function updateCountdown() {
            const now = new Date();
            const timeLeft = launchDate - now;
            
            if (timeLeft <= 0) {
                // Timer finished - show Download Now button
                ctaButton.textContent = 'Download Now';
                ctaButton.classList.remove('disabled');
                ctaButton.onclick = function() {
                    window.location.href = '/hiotaku/relised/hiotaku.apk';
                };
                return;
            }
            
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = days.toString().padStart(2, '0');
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }
        
        // Update immediately and then every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
