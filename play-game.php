<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

$error = '';
$gameId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($gameId)) {
    header('Location: games.php');
    exit;
}

// Get game details
$gameResult = $supabase->select(TABLE_GAMES, '*', ['id' => $gameId]);
if (empty($gameResult) || isset($gameResult['error'])) {
    header('Location: games.php');
    exit;
}

$game = $gameResult[0];

// Check if user has reached daily limit
$remainingPlays = getRemainingGamePlays($_SESSION['user_id']);
if ($remainingPlays <= 0 && !isset($_POST['complete_game'])) {
    setErrorMessage('You have reached your daily game limit (' . DAILY_GAME_LIMIT . ' games). Come back tomorrow!');
    header('Location: games.php');
    exit;
}

// Check per-game daily limit
$maxPlaysPerDay = $game['max_plays_per_day'] ?? 3;
if (hasReachedGameDailyLimit($_SESSION['user_id'], $gameId, $maxPlaysPerDay) && !isset($_POST['complete_game'])) {
    setErrorMessage('You have reached the daily limit for this game (' . $maxPlaysPerDay . ' plays per day). Try another game or come back tomorrow!');
    header('Location: games.php');
    exit;
}

// Check game-specific cooldown
$cooldownRemaining = getGameCooldownRemaining($_SESSION['user_id'], $gameId);
if ($cooldownRemaining > 0 && !isset($_POST['complete_game'])) {
    setErrorMessage('You can play this game again in ' . formatTimeRemaining($cooldownRemaining) . '.');
    header('Location: games.php');
    exit;
}

// Generate session ID for this play
$sessionId = generateGameSessionId();

// Handle game completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_game'])) {
    $score = isset($_POST['game_score']) ? intval($_POST['game_score']) : 0;
    $submittedSessionId = isset($_POST['session_id']) ? sanitize($_POST['session_id']) : '';
    
    // Validate session
    if (empty($submittedSessionId) || !isValidGameSession($submittedSessionId)) {
        setErrorMessage('Invalid game session. Please play the game again.');
        header('Location: games.php');
        exit;
    }
    
    // Check if score meets minimum requirement
    $minScore = $game['min_score_required'] ?? 0;
    if ($score < $minScore) {
        setErrorMessage('Score too low! You need at least ' . $minScore . ' points to earn rewards. You scored: ' . $score);
        header('Location: games.php');
        exit;
    }
    
    // Calculate points (score directly as points)
    $pointsAwarded = $score;
    
    // Award points
    updateUserPoints($_SESSION['user_id'], $pointsAwarded);
    
    // Record game play
    $playData = [
        'user_id' => $_SESSION['user_id'],
        'game_id' => $gameId,
        'points_earned' => $pointsAwarded,
        'score' => $score,
        'session_id' => $submittedSessionId,
        'played_at' => date('Y-m-d H:i:s')
    ];
    $supabase->insert(TABLE_GAME_PLAYS, $playData);
    
    // Update best score
    $isNewBest = updateBestScore($_SESSION['user_id'], $gameId, $score);
    
    // Update session points
    $_SESSION['points'] += $pointsAwarded;
    
    $message = 'Game completed! Score: ' . $score . ' - You earned ' . $pointsAwarded . ' points.';
    if ($isNewBest) {
        $message .= ' 🎉 New personal best!';
    }
    
    setSuccessMessage($message);
    header('Location: dashboard.php');
    exit;
}

// Get user stats for this game
$bestScore = getUserBestScore($_SESSION['user_id'], $gameId);
$playsToday = getTodayGamePlaysForGame($_SESSION['user_id'], $gameId);
$maxPlaysPerDay = $game['max_plays_per_day'] ?? 3;
$playsRemaining = getRemainingGamePlaysForGame($_SESSION['user_id'], $gameId, $maxPlaysPerDay);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🎮 <?php echo htmlspecialchars($game['name']); ?></h1>
            <div class="header-nav">
                <span class="points-badge">💰 <?php echo number_format($_SESSION['points']); ?> points</span>
                <a href="games.php" class="btn btn-small">← Back to Games</a>
            </div>
        </header>

        <main class="game-play-content">
            <div class="game-info-grid">
                <div class="info-card">
                    <h3>ℹ️ Game Info</h3>
                    <p><?php echo htmlspecialchars($game['description']); ?></p>
                    <ul>
                        <li>⏱️ Cooldown: <?php echo $game['play_cooldown_minutes']; ?> minutes</li>
                        <li>🎯 Minimum Score: <?php echo $game['min_score_required']; ?> points</li>
                        <li>💰 Reward: Your score = Your points</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3>📊 Your Stats</h3>
                    <ul>
                        <li>🏆 Best Score: <strong><?php echo number_format($bestScore); ?></strong></li>
                        <li>🎮 This Game Today: <strong><?php echo $playsToday; ?>/<?php echo $maxPlaysPerDay; ?></strong></li>
                        <li>🎲 All Games Today: <strong><?php echo $remainingPlays; ?>/<?php echo DAILY_GAME_LIMIT; ?></strong></li>
                    </ul>
                </div>
            </div>

            <div class="game-container">
                <iframe src="<?php echo htmlspecialchars($game['file_path']); ?>" 
                        frameborder="0" 
                        width="100%" 
                        height="600px"
                        id="gameFrame"></iframe>
            </div>

            <div class="game-actions">
                <div id="scoreDisplay" style="display:none; background:#d4edda; padding:20px; border-radius:10px; margin-bottom:20px;">
                    <h3 style="color:#155724; margin:0 0 15px 0;">🎮 Game Over!</h3>
                    <p style="margin:0; font-size:1.2em;"><strong>Your Score:</strong> <span id="finalScore">0</span></p>
                    <p style="margin:10px 0 0 0; font-size:1.2em;"><strong>Points You'll Earn:</strong> <span id="pointsEarned">0</span></p>
                    <p id="minScoreWarning" style="display:none; margin:10px 0 0 0; color:#721c24; background:#f8d7da; padding:10px; border-radius:5px;"></p>
                    <p id="newBestBadge" style="display:none; margin:10px 0 0 0; font-size:1.3em; color:#155724;">🎉 NEW PERSONAL BEST!</p>
                </div>
                
                <form method="POST" action="" id="completeForm">
                    <input type="hidden" name="complete_game" value="1">
                    <input type="hidden" name="game_score" id="gameScore" value="0">
                    <input type="hidden" name="session_id" id="sessionId" value="<?php echo $sessionId; ?>">
                    <button type="submit" class="btn btn-primary btn-large" id="claimBtn" disabled>
                        ⏳ Play the game to claim points
                    </button>
                </form>
                <p class="game-note">Play the game until game over, then click the button to claim your points!</p>
            </div>
        </main>
    </div>

    <script>
    const minScoreRequired = <?php echo $game['min_score_required']; ?>;
    const bestScore = <?php echo $bestScore; ?>;
    let gameCompleted = false;
    
    // Listen for game completion message from iframe
    window.addEventListener('message', function(event) {
        console.log('Message received:', event.data);
        
        if (event.data.type === 'gameComplete' && !gameCompleted) {
            gameCompleted = true;
            var score = parseInt(event.data.score) || 0;
            var points = score; // Direct score to points
            
            console.log('Game completed! Score:', score, 'Points:', points);
            
            // Update hidden form fields
            document.getElementById('gameScore').value = score;
            
            // Show score display
            document.getElementById('finalScore').textContent = score;
            document.getElementById('pointsEarned').textContent = points;
            document.getElementById('scoreDisplay').style.display = 'block';
            
            // Check if score meets minimum
            if (score < minScoreRequired) {
                console.log('Score too low. Required:', minScoreRequired);
                document.getElementById('minScoreWarning').textContent = 
                    '⚠️ Score too low! You need at least ' + minScoreRequired + ' points to earn rewards.';
                document.getElementById('minScoreWarning').style.display = 'block';
                document.getElementById('claimBtn').textContent = 'Score Too Low - Try Again';
                document.getElementById('claimBtn').disabled = true;
                document.getElementById('claimBtn').classList.add('btn-disabled');
            } else {
                console.log('Score meets requirement!');
                // Enable claim button
                document.getElementById('claimBtn').textContent = 'Claim ' + points + ' Points!';
                document.getElementById('claimBtn').disabled = false;
                document.getElementById('claimBtn').classList.remove('btn-disabled');
                
                // Show new best badge if applicable
                if (score > bestScore) {
                    document.getElementById('newBestBadge').style.display = 'block';
                }
            }
        }
    });
    
    // Warn before leaving if game in progress
    window.addEventListener('beforeunload', function(e) {
        if (!gameCompleted && document.getElementById('scoreDisplay').style.display === 'none') {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    </script>
</body>
</html>