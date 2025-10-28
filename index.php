<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Earn Points by Playing Games</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="hero">
            <h1>🎮 <?php echo SITE_NAME; ?></h1>
            <p class="tagline">Play games, solve captchas, and earn points!</p>
        </header>

        <main class="landing-content">
            <section class="features">
                <h2>How It Works</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3>Sign Up</h3>
                        <p>Create your free account and get started</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎮</div>
                        <h3>Play Games</h3>
                        <p>Enjoy fun HTML5 games and earn <?php echo POINTS_PER_GAME; ?> points per game</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🤖</div>
                        <h3>Solve Captchas</h3>
                        <p>Complete captchas and earn <?php echo POINTS_PER_CAPTCHA; ?> points each</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3>Refer Friends</h3>
                        <p>Earn <?php echo POINTS_REFERRAL_COMMISSION; ?> points for each approved referral</p>
                    </div>
                </div>
            </section>

            <section class="cta-section">
                <h2>Ready to Start Earning?</h2>
                <div class="button-group">
                    <a href="signup.php" class="btn btn-primary">Sign Up Now</a>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>