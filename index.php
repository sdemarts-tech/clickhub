<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Earn Points Playing Games</title>
    <?php include 'includes/header-links.php'; ?>
</head>
<body>
    <div class="landing-container">
        <header class="hero">
            <h1>🎮 <?php echo SITE_NAME; ?></h1>
            <p class="tagline">Play Games • Solve Captchas • Earn Points • Refer Friends</p>
        </header>

        <div class="features">
            <div class="feature-card">
                <h3>🎯 Play Games</h3>
                <p>Earn <?php echo POINTS_PER_GAME; ?> points per game completion</p>
            </div>
            <div class="feature-card">
                <h3>🤖 Solve Captchas</h3>
                <p>Get <?php echo POINTS_PER_CAPTCHA; ?> points for each captcha</p>
            </div>
            <div class="feature-card">
                <h3>👥 Refer Friends</h3>
                <p>Earn <?php echo POINTS_REFERRAL_COMMISSION; ?> points per referral</p>
            </div>
        </div>

        <div class="cta-buttons">
            <a href="signup.php" class="btn btn-primary">Sign Up Now</a>
            <a href="login.php" class="btn btn-secondary">Login</a>
        </div>

        <div class="info">
            <p>Start earning points today! Play exciting games, complete simple tasks, and build your rewards.</p>
        </div>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .landing-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .hero {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero h1 {
            font-size: 42px;
            color: #333;
            margin-bottom: 10px;
        }

        .tagline {
            font-size: 18px;
            color: #666;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }

        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #667eea;
        }

        .feature-card p {
            color: #666;
            font-size: 14px;
        }

        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .btn {
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .info {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .hero h1 {
                font-size: 32px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>