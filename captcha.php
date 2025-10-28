<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

$error = '';
$success = '';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cf-turnstile-response'])) {
    $token = $_POST['cf-turnstile-response'];
    
    // Check daily limit
    if (!checkCaptchaLimit($_SESSION['user_id'])) {
        $error = 'You have reached your daily captcha limit';
    } else {
        // Verify the captcha
        if (verifyTurnstile($token)) {
            // Award points
            $pointsAwarded = POINTS_PER_CAPTCHA;
            updateUserPoints($_SESSION['user_id'], $pointsAwarded);
            
            // Record captcha solve
            $captchaData = [
                'user_id' => $_SESSION['user_id'],
                'points_earned' => $pointsAwarded,
                'solved_at' => date('Y-m-d H:i:s')
            ];
            $supabase->insert(TABLE_CAPTCHA_SOLVES, $captchaData);
            
            // Update session points
            $_SESSION['points'] += $pointsAwarded;
            
            $success = 'Captcha solved successfully! You earned ' . $pointsAwarded . ' points.';
        } else {
            $error = 'Captcha verification failed. Please try again.';
        }
    }
}

$todayCaptchaCount = getTodayCaptchaCount($_SESSION['user_id']);
$captchaRemaining = DAILY_CAPTCHA_LIMIT - $todayCaptchaCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solve Captcha - <?php echo SITE_NAME; ?></title>
    
  
  <?php include 'includes/header-links.php'; ?>
  
  
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🤖 Solve Captcha</h1>
            <div class="header-nav">
                <span class="points-badge">💰 <?php echo number_format($_SESSION['points']); ?> points</span>
                <a href="dashboard.php" class="btn btn-small">← Dashboard</a>
            </div>
        </header>

        <main class="captcha-content">
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                    <p><a href="captcha.php" class="btn btn-primary">Solve Another</a></p>
                </div>
            <?php else: ?>
                <div class="captcha-box">
                    <div class="info-box">
                        <h3>📊 Today's Progress</h3>
                        <p>Captchas solved: <strong><?php echo $todayCaptchaCount; ?></strong> / <?php echo DAILY_CAPTCHA_LIMIT; ?></p>
                        <p>Remaining: <strong><?php echo $captchaRemaining; ?></strong></p>
                        <p>Points per captcha: <strong><?php echo POINTS_PER_CAPTCHA; ?> points</strong></p>
                    </div>

                    <?php if ($captchaRemaining > 0): ?>
                        <div class="captcha-form">
                            <h3>Complete the captcha below:</h3>
                            <form method="POST" action="">
                                <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"></div>
                                <br>
                                <button type="submit" class="btn btn-primary btn-block">Submit & Earn Points</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="info-message">
                            <h3>Daily Limit Reached</h3>
                            <p>You've reached your daily captcha limit. Come back tomorrow!</p>
                            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>