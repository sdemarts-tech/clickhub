<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

// Get updated user data
$user = getUserById($_SESSION['user_id']);
if ($user) {
    $_SESSION['points'] = $user['points'];
}

// Get referral link
$referralLink = SITE_URL . 'signup.php?ref=' . urlencode($_SESSION['username']);

// Count today's activities
$todayCaptchaCount = getTodayCaptchaCount($_SESSION['user_id']);
$captchaRemaining = DAILY_CAPTCHA_LIMIT - $todayCaptchaCount;

$todayGamePlays = getTodayGamePlaysCount($_SESSION['user_id']);
$gamesRemaining = getRemainingGamePlays($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
     <?php include 'includes/header-links.php'; ?>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>🎮 <?php echo SITE_NAME; ?></h1>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="points-badge">💰 <?php echo number_format($_SESSION['points']); ?> points</span>
                    <a href="logout.php" class="btn btn-small">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <?php
            $successMsg = getSuccessMessage();
            $errorMsg = getErrorMessage();
            if ($successMsg):
            ?>
                <div class="success-message"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="error-message"><?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($_SESSION['points']); ?></h3>
                        <p>Total Points</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎮</div>
                    <div class="stat-info">
                        <h3><?php echo $gamesRemaining; ?></h3>
                        <p>Games Remaining Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🤖</div>
                    <div class="stat-info">
                        <h3><?php echo $captchaRemaining; ?></h3>
                        <p>Captchas Remaining Today</p>
                    </div>
                </div>
            </div>

            <div class="action-grid">
                <div class="action-card">
                    <h2>🎮 Play Games</h2>
                    <p>Earn points equal to your score!</p>
                    <p><strong><?php echo $todayGamePlays; ?> / <?php echo DAILY_GAME_LIMIT; ?></strong> games played today</p>
                    <?php if ($gamesRemaining > 0): ?>
                        <a href="games.php" class="btn btn-primary">Browse Games</a>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled>Daily Limit Reached</button>
                    <?php endif; ?>
                </div>
                
                <div class="action-card">
                    <h2>🤖 Solve Captchas</h2>
                    <p>Earn <?php echo POINTS_PER_CAPTCHA; ?> points per captcha</p>
                    <?php if ($captchaRemaining > 0): ?>
                        <a href="captcha.php" class="btn btn-primary">Solve Captcha</a>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled>Daily Limit Reached</button>
                    <?php endif; ?>
                </div>
                
                <div class="action-card">
                    <h2>👥 Refer Friends</h2>
                    <p>Earn <?php echo POINTS_REFERRAL_COMMISSION; ?> points per approved referral</p>
                    <div class="referral-box">
                        <input type="text" value="<?php echo $referralLink; ?>" id="referralLink" readonly>
                        <button onclick="copyReferralLink()" class="btn btn-secondary">Copy Link</button>
                    </div>
                </div>
                
                <div class="action-card">
                    <h2>🏆 Leaderboard</h2>
                    <p>See top players and your ranking</p>
                    <a href="leaderboard.php" class="btn btn-primary">View Leaderboard</a>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <div class="admin-section">
                <h2>⚙️ Admin Panel</h2>
                <a href="admin/index.php" class="btn btn-primary">Go to Admin Dashboard</a>
            </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </footer>
    </div>

    <script>
    function copyReferralLink() {
        const input = document.getElementById('referralLink');
        input.select();
        document.execCommand('copy');
        alert('Referral link copied to clipboard!');
    }
    </script>
</body>
</html>