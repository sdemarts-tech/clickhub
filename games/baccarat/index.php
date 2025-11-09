<?php
// Baccarat game entry point
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? '';
$userName = $_SESSION['username'] ?? 'Player';
$userPoints = isset($_SESSION['points']) ? (int)$_SESSION['points'] : 1000;

$rootPath = dirname(dirname(__DIR__));
$configPath = $rootPath . '/includes/config.php';
$supabasePath = $rootPath . '/includes/supabase.php';
$functionsPath = $rootPath . '/includes/functions.php';

if (file_exists($configPath) && file_exists($supabasePath) && file_exists($functionsPath)) {
    try {
        $previous = error_reporting(E_ALL & ~E_WARNING);
        require_once $configPath;
        require_once $supabasePath;
        require_once $functionsPath;
        error_reporting($previous);

        if (!empty($userId) && function_exists('getUserById')) {
            $user = getUserById($userId);
            if ($user) {
                $userName = $user['username'] ?? $userName;
                $userPoints = isset($user['points']) ? (int)$user['points'] : $userPoints;
            }
        }
    } catch (Exception $e) {
        error_log('Baccarat DB Error: ' . $e->getMessage());
    }
}

if (empty($userId)) {
    $userId = 'guest';
}

if ($userPoints <= 0) {
    $userPoints = 500;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>VIP Baccarat Table</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
    <script>
        window.BACCARAT_USER = {
            id: <?php echo json_encode($userId); ?>,
            name: <?php echo json_encode($userName); ?>,
            balance: <?php echo (int)$userPoints; ?>
        };
    </script>
</head>
<body>
    <div id="baccarat-app">
        <header class="top-bar">
            <div class="brand">🃏 VIP Baccarat</div>
            <div class="round-info">
                <div class="timer">
                    <span id="round-timer">15</span>s
                    <div class="timer-bar"><div id="timer-progress"></div></div>
                </div>
                <div class="round-id">Round #<span id="round-number">0001</span></div>
            </div>
            <div class="balance-panel">
                <div class="player-name">Welcome, <span id="player-name"></span></div>
                <div class="balance">Balance: <span id="player-balance"></span> pts</div>
                <div class="current-bet">Current Bet: <span id="current-bet">0</span> pts</div>
            </div>
        </header>

        <main class="play-area">
            <section class="table-zone">
                <div class="bet-zone banker" data-bet="banker">
                    <div class="label">Banker</div>
                    <div class="odds">1 : 1</div>
                    <div class="bet-amount" id="bet-banker">0</div>
                </div>
                <div class="bet-zone tie" data-bet="tie">
                    <div class="label">Tie</div>
                    <div class="odds">8 : 1</div>
                    <div class="bet-amount" id="bet-tie">0</div>
                </div>
                <div class="bet-zone player" data-bet="player">
                    <div class="label">Player</div>
                    <div class="odds">1 : 1</div>
                    <div class="bet-amount" id="bet-player">0</div>
                </div>

                <div class="card-lanes">
                    <div class="lane player">
                        <div class="lane-label">Player</div>
                        <div class="cards" id="player-cards"></div>
                        <div class="total" id="player-total">0</div>
                    </div>
                    <div class="lane banker">
                        <div class="lane-label">Banker</div>
                        <div class="cards" id="banker-cards"></div>
                        <div class="total" id="banker-total">0</div>
                    </div>
                </div>

                <div class="result-banner" id="result-banner">
                    <span class="winner" id="result-text">Player Wins!</span>
                    <span class="payout" id="result-payout">+0</span>
                </div>
            </section>

            <aside class="history-panel">
                <div class="history-tabs">
                    <button class="tab active" data-board="bead">Bead Road</button>
                    <button class="tab" data-board="big">Big Road</button>
                    <button class="tab" data-board="big-eye">Big Eye Boy</button>
                    <button class="tab" data-board="small">Small Road</button>
                    <button class="tab" data-board="roach">Cockroach Pig</button>
                    <button class="tab" data-board="summary">Summary</button>
                </div>
                <div class="board-container">
                    <canvas id="board-canvas" width="320" height="360"></canvas>
                    <div id="summary-stats" class="summary hidden">
                        <div class="stat"><span>🏆 Banker</span><strong id="stat-banker">0</strong></div>
                        <div class="stat"><span>🧢 Player</span><strong id="stat-player">0</strong></div>
                        <div class="stat"><span>🤝 Ties</span><strong id="stat-tie">0</strong></div>
                    </div>
                </div>
                <div class="bet-history">
                    <div class="history-header">
                        <span>Bet History</span>
                        <div class="filters">
                            <button class="filter active" data-filter="mine">My Bets</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Bet</th>
                                <th>Result</th>
                                <th>Net</th>
                            </tr>
                        </thead>
                        <tbody id="history-body"></tbody>
                    </table>
                </div>
            </aside>
        </main>

        <footer class="control-bar">
            <div class="chip-tray" id="chip-tray">
                <button class="chip" data-value="10">10</button>
                <button class="chip" data-value="50">50</button>
                <button class="chip" data-value="100">100</button>
                <button class="chip" data-value="500">500</button>
                <button class="chip" data-value="1000">1K</button>
                <button class="chip" data-value="5000">5K</button>
                <button class="chip" data-value="10000">10K</button>
            </div>
            <div class="controls">
                <button id="btn-clear" class="btn ghost">Clear</button>
                <button id="btn-rebet" class="btn ghost">Rebet</button>
                <button id="btn-double" class="btn ghost">Double</button>
                <button id="btn-deal" class="btn primary">Deal</button>
            </div>
            <div class="toggles">
                <button id="toggle-music" class="toggle">🎵 Music</button>
                <button id="toggle-sfx" class="toggle active">🔊 SFX</button>
            </div>
        </footer>
    </div>

    <div id="toast"></div>

    <audio id="audio-chip" src="assets/audio/chip.mp3" preload="auto"></audio>
    <audio id="audio-card" src="assets/audio/card.mp3" preload="auto"></audio>
    <audio id="audio-win" src="assets/audio/win.mp3" preload="auto"></audio>
    <audio id="audio-tie" src="assets/audio/tie.mp3" preload="auto"></audio>
    <audio id="audio-music" src="assets/audio/music.mp3" preload="auto" loop></audio>

    <script type="module" src="js/main.js?v=1.0"></script>
</body>
</html>
