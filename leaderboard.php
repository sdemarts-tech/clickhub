<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

requireLogin();

// Get global leaderboard (top 20 by total points)
$url = SUPABASE_URL . '/rest/v1/users?status=eq.approved&order=points.desc&limit=20';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY
]);
$response = curl_exec($ch);
curl_close($ch);
$globalLeaders = json_decode($response, true);

// Find current user's rank
$url = SUPABASE_URL . '/rest/v1/users?status=eq.approved&order=points.desc';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY
]);
$response = curl_exec($ch);
curl_close($ch);
$allUsers = json_decode($response, true);

$userRank = 0;
foreach ($allUsers as $index => $u) {
    if ($u['id'] === $_SESSION['user_id']) {
        $userRank = $index + 1;
        break;
    }
}

// Get all active games
$games = $supabase->select(TABLE_GAMES, '*', ['status' => 'active']);
if (isset($games['error'])) {
    $games = [];
}

// Get selected game for per-game leaderboard
$selectedGameId = isset($_GET['game']) ? sanitize($_GET['game']) : (count($games) > 0 ? $games[0]['id'] : null);

// Get per-game leaderboard if game selected
$gameLeaders = [];
$selectedGame = null;
if ($selectedGameId) {
    // Get game info
    foreach ($games as $g) {
        if ($g['id'] === $selectedGameId) {
            $selectedGame = $g;
            break;
        }
    }
    
    // Get top scores for this game
    $url = SUPABASE_URL . '/rest/v1/game_best_scores?game_id=eq.' . $selectedGameId . '&order=best_score.desc&limit=20';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $gameLeaders = json_decode($response, true);
    
    // Ensure gameLeaders is an array
    if (!is_array($gameLeaders)) {
        $gameLeaders = [];
    }
    
    // Get usernames for game leaders
    if (!empty($gameLeaders)) {
        foreach ($gameLeaders as &$leader) {
            if (isset($leader['user_id'])) {
                $user = getUserById($leader['user_id']);
                $leader['username'] = $user ? $user['username'] : 'Unknown';
            } else {
                $leader['username'] = 'Unknown';
            }
        }
        unset($leader); // Break reference
    }
    
    // Find user's rank for this game
    $url = SUPABASE_URL . '/rest/v1/game_best_scores?game_id=eq.' . $selectedGameId . '&order=best_score.desc';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $allGameScores = json_decode($response, true);
    
    // Ensure it's an array
    if (!is_array($allGameScores)) {
        $allGameScores = [];
    }
    
    $gameUserRank = 0;
    $userBestScore = 0;
    if (!empty($allGameScores)) {
        foreach ($allGameScores as $index => $score) {
            if (isset($score['user_id']) && $score['user_id'] === $_SESSION['user_id']) {
                $gameUserRank = $index + 1;
                $userBestScore = isset($score['best_score']) ? $score['best_score'] : 0;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🏆 Leaderboard</h1>
            <div class="header-nav">
                <span class="points-badge">💰 <?php echo number_format($_SESSION['points']); ?> points</span>
                <a href="dashboard.php" class="btn btn-small">← Dashboard</a>
            </div>
        </header>

        <main class="leaderboard-content">
            <div class="leaderboard-tabs">
                <button class="tab-btn active" onclick="showTab('global')">🌍 Global</button>
                <button class="tab-btn" onclick="showTab('games')">🎮 Games</button>
            </div>

            <!-- Global Leaderboard -->
            <div id="global-tab" class="tab-content active">
                <div class="user-rank-box">
                    <h3>Your Global Rank</h3>
                    <p class="rank-display">
                        <?php if ($userRank > 0): ?>
                            #<?php echo $userRank; ?> with <?php echo number_format($_SESSION['points']); ?> points
                        <?php else: ?>
                            Not ranked yet - play games to get on the leaderboard!
                        <?php endif; ?>
                    </p>
                </div>

                <div class="leaderboard-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th>Total Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($globalLeaders)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No players yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($globalLeaders as $index => $leader): ?>
                                    <tr class="<?php echo $leader['id'] === $_SESSION['user_id'] ? 'highlight-row' : ''; ?>">
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <span class="rank-medal"><?php echo ['🥇', '🥈', '🥉'][$index]; ?></span>
                                            <?php else: ?>
                                                #<?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($leader['username']); ?>
                                            <?php if ($leader['id'] === $_SESSION['user_id']): ?>
                                                <span class="badge-you">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo number_format($leader['points']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Game Leaderboard -->
            <div id="games-tab" class="tab-content">
                <?php if (count($games) > 0): ?>
                    <div class="game-selector">
                        <label>Select Game:</label>
                        <select onchange="window.location.href='leaderboard.php?game=' + this.value">
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['id']; ?>" 
                                        <?php echo $game['id'] === $selectedGameId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($selectedGame): ?>
                        <div class="user-rank-box">
                            <h3>Your Rank in <?php echo htmlspecialchars($selectedGame['name']); ?></h3>
                            <p class="rank-display">
                                <?php if ($gameUserRank > 0): ?>
                                    #<?php echo $gameUserRank; ?> with best score: <?php echo number_format($userBestScore); ?>
                                <?php else: ?>
                                    Not ranked yet - <a href="play-game.php?id=<?php echo $selectedGameId; ?>">play now!</a>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="leaderboard-table">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Player</th>
                                        <th>Best Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($gameLeaders)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No scores yet - be the first!</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($gameLeaders as $index => $leader): ?>
                                            <tr class="<?php echo $leader['user_id'] === $_SESSION['user_id'] ? 'highlight-row' : ''; ?>">
                                                <td>
                                                    <?php if ($index < 3): ?>
                                                        <span class="rank-medal"><?php echo ['🥇', '🥈', '🥉'][$index]; ?></span>
                                                    <?php else: ?>
                                                        #<?php echo $index + 1; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo isset($leader['username']) ? htmlspecialchars($leader['username']) : 'Unknown'; ?>
                                                    <?php if (isset($leader['user_id']) && $leader['user_id'] === $_SESSION['user_id']): ?>
                                                        <span class="badge-you">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo isset($leader['best_score']) ? number_format($leader['best_score']) : 0; ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="info-message">
                        <p>No games available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }
    </script>
</body>
</html>