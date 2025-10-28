<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

// Get remaining plays
$remainingPlays = getRemainingGamePlays($_SESSION['user_id']);
$todayPlays = getTodayGamePlaysCount($_SESSION['user_id']);

// Get all active games
$games = $supabase->select(TABLE_GAMES, '*', ['status' => 'active']);
if (isset($games['error'])) {
    $games = [];
}

// Get ALL game plays for today at once (optimization)
$today = date('Y-m-d');
$url = SUPABASE_URL . '/rest/v1/' . TABLE_GAME_PLAYS . 
       '?user_id=eq.' . $_SESSION['user_id'] . 
       '&played_at=gte.' . $today . 'T00:00:00' .
       '&order=played_at.desc';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY
]);
$response = curl_exec($ch);
curl_close($ch);
$todayPlaysData = json_decode($response, true) ?: [];

// Count plays per game
$playsPerGame = [];
foreach ($todayPlaysData as $play) {
    $gameId = $play['game_id'];
    if (!isset($playsPerGame[$gameId])) {
        $playsPerGame[$gameId] = [];
    }
    $playsPerGame[$gameId][] = $play;
}

// Add status info for each game
foreach ($games as &$game) {
    $gameId = $game['id'];
    $maxPlaysPerDay = intval($game['max_plays_per_day'] ?? 3);
    
    // Get plays count for this game
    $gamePlays = $playsPerGame[$gameId] ?? [];
    $playsToday = count($gamePlays);
    
    // Calculate cooldown from last play
    $cooldownRemaining = 0;
    if (!empty($gamePlays)) {
        $lastPlayTime = strtotime($gamePlays[0]['played_at']);
        $cooldownMinutes = intval($game['play_cooldown_minutes'] ?? 60);
        $cooldownSeconds = $cooldownMinutes * 60;
        $nextAvailableTime = $lastPlayTime + $cooldownSeconds;
        $remainingSeconds = $nextAvailableTime - time();
        $cooldownRemaining = max(0, ceil($remainingSeconds / 60));
    }
    
    // Get best score (only call if needed)
    $bestScore = 0;
    if (function_exists('getUserBestScore')) {
        $bestScore = getUserBestScore($_SESSION['user_id'], $gameId);
    }
    
    // Set game data
    $game['plays_today'] = $playsToday;
    $game['max_plays_per_day'] = $maxPlaysPerDay;
    $game['plays_remaining'] = max(0, $maxPlaysPerDay - $playsToday);
    $game['reached_game_limit'] = ($playsToday >= $maxPlaysPerDay);
    $game['cooldown_remaining'] = $cooldownRemaining;
    $game['can_play'] = ($cooldownRemaining == 0 && !$game['reached_game_limit']);
    $game['best_score'] = $bestScore;
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

            <div class="games-stats-box">
                <h3>📊 Today's Activity</h3>
                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-label">Games Played:</span>
                        <span class="stat-value"><?php echo $todayPlays; ?> / <?php echo DAILY_GAME_LIMIT; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Remaining:</span>
                        <span class="stat-value <?php echo $remainingPlays > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $remainingPlays; ?> plays left
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($games)): ?>
                <div class="info-message">
                    <h3>No Games Available</h3>
                    <p>Games are coming soon! Check back later.</p>
                    <p>In the meantime, you can earn points by <a href="captcha.php">solving captchas</a>.</p>
                </div>
            <?php else: ?>
                <div class="games-grid">
                    <?php foreach ($games as $game): ?>
                        <div class="game-card <?php echo !$game['can_play'] || $remainingPlays <= 0 ? 'game-locked' : ''; ?>">
                            <div class="game-icon">🎮</div>
                            <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                            <p class="game-description"><?php echo htmlspecialchars($game['description']); ?></p>
                            
                            <div class="game-stats">
                                <div class="game-stat">
                                    <span>🏆 Your Best:</span>
                                    <strong><?php echo number_format($game['best_score']); ?></strong>
                                </div>
                                <div class="game-stat">
                                    <span>🎯 Min Score:</span>
                                    <strong><?php echo $game['min_score_required']; ?></strong>
                                </div>
                                <div class="game-stat">
                                    <span>🎮 Plays Today:</span>
                                    <strong><?php echo $game['plays_today']; ?>/<?php echo $game['max_plays_per_day']; ?></strong>
                                </div>
                                <div class="game-stat">
                                    <span>⏱️ Cooldown:</span>
                                    <strong><?php echo $game['play_cooldown_minutes']; ?> min</strong>
                                </div>
                            </div>
                            
                            <div class="game-footer">
                                <span class="game-points">💰 Score = Points</span>
                                
                                <?php if ($remainingPlays <= 0): ?>
                                    <button class="btn btn-disabled" disabled>
                                        ❌ Daily Limit Reached (All Games)
                                    </button>
                                <?php elseif ($game['reached_game_limit']): ?>
                                    <button class="btn btn-disabled" disabled>
                                        ❌ Game Limit Reached (<?php echo $game['max_plays_per_day']; ?>/day)
                                    </button>
                                <?php elseif ($game['cooldown_remaining'] > 0): ?>
                                    <button class="btn btn-disabled" disabled>
                                        🔒 Available in <?php echo formatTimeRemaining($game['cooldown_remaining']); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="play-game.php?id=<?php echo $game['id']; ?>" class="btn btn-primary">
                                        ✅ Play Now (<?php echo $game['plays_remaining']; ?> left)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box" style="margin-top: 40px;">
                <h3>ℹ️ How It Works</h3>
                <ul>
                    <li>🎮 Play games and your score becomes your points (e.g., score 150 = 150 points)</li>
                    <li>🎯 You must reach the minimum score to earn points</li>
                    <li>⏱️ Each game has a cooldown period before you can play again</li>
                    <li>🔢 Each game can be played a limited number of times per day</li>
                    <li>📊 You can play up to <?php echo DAILY_GAME_LIMIT; ?> games per day (across all games)</li>
                    <li>🏆 Beat your personal best to climb the leaderboard!</li>
                </ul>
                <p style="margin-top: 15px;">
                    <a href="leaderboard.php" class="btn btn-secondary">🏆 View Leaderboard</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>