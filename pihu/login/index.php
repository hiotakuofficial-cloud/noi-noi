<?php
session_start();

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        require_once '../api/auth.php';
        $result = loginAdmin($username, $password);
        
        if ($result['success']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $result['admin']['id'];
            $_SESSION['admin_username'] = $result['admin']['username'];
            $_SESSION['admin_type'] = $result['admin']['user_type'];
            header('Location: ../index.php');
            exit;
        } else {
            if ($result['error'] === 'banned') {
                header('Location: ../banned.php');
                exit;
            }
            $error = $result['error'];
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Hiotaku Control</title>
<link rel="icon" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --bg: #0a0a0c;
    --surface: #111115;
    --surface2: #17171d;
    --border: rgba(255,255,255,0.07);
    --border-hover: rgba(255,255,255,0.14);
    --text: #f0eff5;
    --muted: #6e6d7a;
    --accent: #c8b8ff;
    --accent2: #8b6fff;
    --glow: rgba(139,111,255,0.18);
  }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
  }

  .bg-canvas {
    position: fixed;
    inset: 0;
    z-index: 0;
  }

  canvas {
    width: 100%;
    height: 100%;
  }

  .noise {
    position: fixed;
    inset: 0;
    z-index: 1;
    opacity: 0.03;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 200px;
    pointer-events: none;
  }

  .scene {
    position: fixed;
    inset: 0;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    justify-items: center;
  }

  .left-panel {
    padding: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
    gap: 32px;
    opacity: 0;
    transform: translateX(-24px);
    animation: slideIn 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards;
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .brand-mark {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 0 20px var(--glow);
  }

  .brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .brand-name {
    font-family: 'DM Serif Display', serif;
    font-size: 1.4rem;
    letter-spacing: -0.02em;
    color: var(--text);
  }

  .hero-text {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 400px;
  }

  .hero-text h1 {
    font-family: 'DM Serif Display', serif;
    font-size: clamp(2.4rem, 4vw, 3.4rem);
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--text);
  }

  .hero-text h1 em {
    font-style: italic;
    color: var(--accent);
  }

  .hero-text p {
    font-size: 0.95rem;
    line-height: 1.7;
    color: var(--muted);
    font-weight: 300;
    max-width: 320px;
  }

  .testimonial {
    padding: 20px 24px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    max-width: 360px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
  }

  .testimonial::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent2), transparent);
    opacity: 0.6;
  }

  .testimonial-text {
    font-size: 0.88rem;
    line-height: 1.6;
    color: rgba(240,239,245,0.8);
    font-weight: 300;
  }

  .right-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 60px 40px;
    opacity: 0;
    transform: translateX(24px);
    animation: slideIn2 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.35s forwards;
  }

  .card {
    width: 100%;
    max-width: 400px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    overflow: hidden;
  }

  .card::before {
    content: '';
    position: absolute;
    top: 0; left: 10%; right: 10%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(200,184,255,0.5), transparent);
  }

  .card-glow {
    position: absolute;
    inset: -1px;
    border-radius: 24px;
    background: transparent;
    border: 1px solid transparent;
    transition: border-color 0.4s ease;
    pointer-events: none;
    z-index: 0;
  }

  .card:hover .card-glow {
    border-color: rgba(139,111,255,0.25);
    box-shadow: 0 0 40px rgba(139,111,255,0.08) inset;
  }

  .card-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 28px;
  }

  .card-header {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .card-header h2 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.7rem;
    letter-spacing: -0.03em;
    color: var(--text);
  }

  .card-header p {
    font-size: 0.85rem;
    color: var(--muted);
    font-weight: 300;
  }

  .form-fields {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .field-group label {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--muted);
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-wrap input {
    width: 100%;
    padding: 13px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 300;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
  }

  .input-wrap input::placeholder { color: var(--muted); opacity: 0.6; }

  .input-wrap input:focus {
    border-color: var(--accent2);
    box-shadow: 0 0 0 3px rgba(139,111,255,0.1);
    background: rgba(139,111,255,0.03);
  }

  .error-message {
    background: rgba(239,68,68,0.1);
    color: #fca5a5;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
    border: 1px solid rgba(239,68,68,0.2);
  }

  .submit-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent2), #a78bfa);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 24px rgba(139,111,255,0.3);
  }

  .submit-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #9f75ff, #c4acff);
    opacity: 0;
    transition: opacity 0.3s;
  }

  .submit-btn:hover::before { opacity: 1; }

  .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(139,111,255,0.45);
  }

  .submit-btn:active { transform: translateY(0); }

  .submit-btn span { position: relative; z-index: 1; }

  @keyframes slideIn {
    to { opacity: 1; transform: translateX(0); }
  }
  @keyframes slideIn2 {
    to { opacity: 1; transform: translateX(0); }
  }

  @media (max-width: 768px) {
    .scene { grid-template-columns: 1fr; }
    .left-panel { display: none; }
    .right-panel { padding: 24px; }
  }
</style>
</head>
<body>

<div class="bg-canvas">
  <canvas id="bg"></canvas>
</div>
<div class="noise"></div>

<div class="scene">
  <div class="left-panel">
    <div class="brand">
      <div class="brand-mark">
        <img src="../assets/logo.png" alt="Hiotaku">
      </div>
      <span class="brand-name">Hiotaku</span>
    </div>

    <div class="hero-text">
      <h1>Control your<br><em>anime empire</em></h1>
      <p>Manage notifications, users, and system settings from one powerful dashboard. Built for efficiency.</p>
    </div>

    <div class="testimonial">
      <p class="testimonial-text">"The most intuitive admin panel I've used. Everything just works seamlessly."</p>
    </div>
  </div>

  <div class="right-panel">
    <div class="card">
      <div class="card-glow"></div>
      <div class="card-inner">
        <div class="card-header">
          <h2>Welcome back</h2>
          <p>Sign in to access control panel</p>
        </div>

        <?php if ($error): ?>
          <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="form-fields" method="POST">
          <div class="field-group">
            <label for="username">Username</label>
            <div class="input-wrap">
              <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
            </div>
          </div>

          <div class="field-group">
            <label for="password">Password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
          </div>

          <button type="submit" class="submit-btn">
            <span>Sign in</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const canvas = document.getElementById('bg');
const ctx = canvas.getContext('2d');
let W, H, particles = [], time = 0;

function resize() {
  W = canvas.width = window.innerWidth;
  H = canvas.height = window.innerHeight;
}

class Particle {
  constructor() { this.reset(); }
  reset() {
    this.x = Math.random() * W;
    this.y = Math.random() * H;
    this.vx = (Math.random() - 0.5) * 0.25;
    this.vy = (Math.random() - 0.5) * 0.25;
    this.r = Math.random() * 1.4 + 0.4;
    this.life = 0;
    this.maxLife = Math.random() * 400 + 200;
    this.hue = Math.random() * 40 + 240;
  }
  update() {
    this.x += this.vx;
    this.y += this.vy;
    this.life++;
    if (this.life > this.maxLife) this.reset();
  }
  draw() {
    const progress = this.life / this.maxLife;
    const opacity = progress < 0.15 ? progress / 0.15 : progress > 0.85 ? (1 - progress) / 0.15 : 1;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    ctx.fillStyle = `hsla(${this.hue}, 70%, 70%, ${opacity * 0.35})`;
    ctx.fill();
  }
}

function createParticles() {
  particles = [];
  const count = Math.floor((W * H) / 14000);
  for (let i = 0; i < count; i++) {
    const p = new Particle();
    p.life = Math.random() * p.maxLife;
    particles.push(p);
  }
}

const orbs = [
  { x: 0.15, y: 0.35, r: 320, h: 260 },
  { x: 0.82, y: 0.65, r: 280, h: 230 },
  { x: 0.5, y: 0.1, r: 200, h: 280 }
];

function drawOrb(ox, oy, r, h, t) {
  const x = ox * W + Math.sin(t * 0.4 + h) * 30;
  const y = oy * H + Math.cos(t * 0.3 + h) * 20;
  const grad = ctx.createRadialGradient(x, y, 0, x, y, r);
  grad.addColorStop(0, `hsla(${h}, 65%, 45%, 0.12)`);
  grad.addColorStop(1, `hsla(${h}, 65%, 45%, 0)`);
  ctx.beginPath();
  ctx.arc(x, y, r, 0, Math.PI * 2);
  ctx.fillStyle = grad;
  ctx.fill();
}

function animate() {
  requestAnimationFrame(animate);
  time += 0.008;
  ctx.clearRect(0, 0, W, H);

  orbs.forEach(o => drawOrb(o.x, o.y, o.r, o.h, time));

  particles.forEach(p => { p.update(); p.draw(); });

  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const a = particles[i], b = particles[j];
      const dx = a.x - b.x, dy = a.y - b.y;
      const d = Math.sqrt(dx*dx + dy*dy);
      if (d < 80) {
        ctx.beginPath();
        ctx.strokeStyle = `rgba(139,111,255,${(1 - d/80) * 0.06})`;
        ctx.lineWidth = 0.5;
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.stroke();
      }
    }
  }
}

window.addEventListener('resize', () => { resize(); createParticles(); });
resize();
createParticles();
animate();
</script>
</body>
</html>
