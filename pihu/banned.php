<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Suspended - Hiotaku</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Space Grotesk', sans-serif;
  background: #050505;
  color: #fff;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.container {
  max-width: 500px;
  text-align: center;
}
.icon {
  font-size: 5rem;
  color: #ff4d6a;
  margin-bottom: 32px;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(0.95); }
}
h1 {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 16px;
  letter-spacing: -0.03em;
}
p {
  font-size: 1.1rem;
  color: #888;
  line-height: 1.6;
  margin-bottom: 32px;
}
.btn {
  display: inline-block;
  padding: 14px 32px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  transition: all 0.3s;
}
.btn:hover {
  background: rgba(255,255,255,0.1);
  transform: translateY(-2px);
}
</style>
</head>
<body>
<div class="container">
  <div class="icon">
    <i class="fas fa-ban"></i>
  </div>
  <h1>Account Suspended</h1>
  <p>Your admin account has been suspended. Please contact the system administrator for more information.</p>
  <a href="login/" class="btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
</body>
</html>
