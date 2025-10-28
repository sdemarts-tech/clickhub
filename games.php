<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

// Get all active games
$games = $supabase->select(TABLE_GAMES, '*', ['status' => 'active']);
if (isset($games['error'])) {
    $games = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🎮 Available Games</h1>
            <div class="header-nav">
                <span class="points-badge">💰 <?php echo number_format($_SESSION['points']); ?> points</span>
                <a href="dashboard.php" class="btn btn-small">← Dashboard</a>
            </div>
        </header>

        <main class="games-content">
            <?php if (empty($games)): ?>
                <div class="info-message">
                    <h3>No Games Available</h3>
                    <p>Games are coming soon! Check back later.</p>
                    <p>In the meantime, you can earn points by <a href="captcha.php">solving captchas</a>.</p>
                </div>
            <?php else: ?>
                <div class="games-grid">
                    <?php foreach ($games as $game): ?>
                        <div class="game-card">
                            <div class="game-icon">🎮</div>
                            <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                            <p><?php echo htmlspecialchars($game['description']); ?></p>
                            <div class="game-footer">
                                <span class="game-points">💰 <?php echo $game['points_reward']; ?> points</span>
                                <a href="play-game.php?id=<?php echo $game['id']; ?>" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>