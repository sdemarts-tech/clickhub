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

// Handle game completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_game'])) {
    // Award points
    $pointsAwarded = $game['points_reward'];
    updateUserPoints($_SESSION['user_id'], $pointsAwarded);
    
    // Record game play
    $playData = [
        'user_id' => $_SESSION['user_id'],
        'game_id' => $gameId,
        'points_earned' => $pointsAwarded,
        'played_at' => date('Y-m-d H:i:s')
    ];
    $supabase->insert(TABLE_GAME_PLAYS, $playData);
    
    // Update session points
    $_SESSION['points'] += $pointsAwarded;
    
    setSuccessMessage('Game completed! You earned ' . $pointsAwarded . ' points.');
    header('Location: dashboard.php');
    exit;
}
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
            <div class="game-info">
                <p><?php echo htmlspecialchars($game['description']); ?></p>
                <p><strong>Reward:</strong> <?php echo $game['points_reward']; ?> points</p>
            </div>

            <div class="game-container">
                <iframe src="<?php echo htmlspecialchars($game['file_path']); ?>" 
                        frameborder="0" 
                        width="100%" 
                        height="600px"
                        id="gameFrame"></iframe>
            </div>

            <div class="game-actions">
                <form method="POST" action="" id="completeForm">
                    <input type="hidden" name="complete_game" value="1">
                    <button type="submit" class="btn btn-primary btn-large">
                        Complete Game & Claim <?php echo $game['points_reward']; ?> Points
                    </button>
                </form>
                <p class="game-note">Click the button above after playing the game to claim your points.</p>
            </div>
        </main>
    </div>
</body>
</html>