<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user data directly from session (works when loaded in iframe from play-game.php)
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$userPoints = isset($_SESSION['points']) ? (int)$_SESSION['points'] : 500;
$userName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Player';

// Try to load includes for database access (optional - will use database if available)
$rootPath = dirname(dirname(__DIR__));
$configPath = $rootPath . '/includes/config.php';
$supabasePath = $rootPath . '/includes/supabase.php';
$functionsPath = $rootPath . '/includes/functions.php';

// Try to load and get fresh data from database if files exist
if (file_exists($configPath) && file_exists($supabasePath) && file_exists($functionsPath)) {
    try {
        // Suppress session ini_set warnings since session is already active from parent page
        // These warnings don't affect functionality - config.php tries to set session settings
        // but they're already set when session_start() was called in play-game.php
        $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING);
        
        require_once $configPath;
        require_once $supabasePath;
        require_once $functionsPath;
        
        // Restore error reporting
        error_reporting($oldErrorReporting);
        
        // Try to get user from database if logged in
        if (!empty($userId) && function_exists('getUserById')) {
            global $supabase;
            $user = getUserById($userId);
            if ($user && isset($user['points'])) {
                $userPoints = (int)$user['points'];
                $userName = $user['username'] ?? 'Player';
            }
        }
    } catch (Exception $e) {
        // Restore error reporting if exception occurred
        error_reporting($oldErrorReporting ?? E_ALL);
        // If database fails, continue with session data
        error_log("Color Game DB Error: " . $e->getMessage());
    }
}

// Set defaults if missing
if (empty($userId)) {
    $userId = 'guest';
}
if ($userPoints <= 0) {
    $userPoints = 500;
}
if (empty($userName)) {
    $userName = 'Player';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>🎲 Color Game - Perya Style</title>
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Pass PHP variables to JavaScript -->
    <script>
        // Pass PHP variables to JavaScript
        window.USER_DATA = {
            userId: '<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>',
            username: <?php echo json_encode($userName, JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            points: <?php echo (int)$userPoints; ?>
        };
    </script>
</head>
<body>
    <!-- Game Container -->
    <div id="game-container">
        <!-- THREE.js Canvas -->
        <div id="canvas-container"></div>
        
        <!-- UI Overlay -->
        <div class="ui-overlay">
            <!-- Header -->
            <header class="game-header">
                <h1>🎲 Color Game</h1>
                <p class="subtitle">Perya-Style Betting • Real Physics</p>
            </header>
            
            <!-- Balance Display -->
            <div class="balance-display">
                <div class="balance-label">Game Balance</div>
                <div class="balance-amount" id="balance"><?php echo number_format($userPoints); ?></div>
                <div class="balance-unit">points</div>
            </div>
            
            <!-- Phase Indicator -->
            <div class="phase-indicator" id="phase">
                Ready to Roll!
            </div>
            
            <!-- View Toggle Button (centered) -->
            <button class="btn-view-toggle-center" id="btn-view-toggle">
                🔄 Side View
            </button>
            
            <!-- Result Display -->
            <div class="result-display" id="result" style="display: none;">
                <div class="result-label">Result:</div>
                <div class="result-dice" id="result-dice"></div>
                <div class="result-outcome" id="result-outcome"></div>
            </div>
            
            <!-- Betting Panel -->
            <div class="betting-panel">
                <div class="bet-chips">
                    <div class="chip-label">Select Bet Amount:</div>
                    <div class="chips">
                        <button class="chip" data-amount="5">5</button>
                        <button class="chip" data-amount="10">10</button>
                        <button class="chip selected" data-amount="50">50</button>
                        <button class="chip" data-amount="100">100</button>
                        <button class="chip" data-amount="500">500</button>
                    </div>
                </div>
                
                <div class="color-panels">
                    <!-- Row 1 -->
                    <div class="color-row">
                        <div class="color-panel" data-color="yellow">
                            <div class="bet-badge" data-color="yellow">0</div>
                            <div class="color-face" style="background: #FFD700;"></div>
                            <div class="color-name">YELLOW</div>
                            <div class="payout">PAY 2×/3×/4×</div>
                        </div>
                        
                        <div class="color-panel" data-color="white">
                            <div class="bet-badge" data-color="white">0</div>
                            <div class="color-face" style="background: #FFFFFF;"></div>
                            <div class="color-name">WHITE</div>
                            <div class="payout">PAY 2×/3×/4×</div>
                        </div>
                        
                        <div class="color-panel" data-color="pink">
                            <div class="bet-badge" data-color="pink">0</div>
                            <div class="color-face" style="background: #FF1493;"></div>
                            <div class="color-name">PINK</div>
                            <div class="payout">PAY 2×/3×/4×</div>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="color-row">
                        <div class="color-panel" data-color="blue">
                            <div class="bet-badge" data-color="blue">0</div>
                            <div class="color-face" style="background: #0080FF;"></div>
                            <div class="color-name">BLUE</div>
                            <div class="payout">PAY 2×/3×/4×</div>
                        </div>
                        
                        <div class="color-panel" data-color="red">
                            <div class="bet-badge" data-color="red">0</div>
                            <div class="color-face" style="background: #FF0000;"></div>
                            <div class="color-name">RED</div>
                            <div class="payout">PAY 2×/3×/3×/4×</div>
                        </div>
                        
                        <div class="color-panel" data-color="green">
                            <div class="bet-badge" data-color="green">0</div>
                            <div class="color-face" style="background: #00FF00;"></div>
                            <div class="color-name">GREEN</div>
                            <div class="payout">PAY 2×/3×/4×</div>
                        </div>
                    </div>
                </div>
                
                <div class="bet-summary">
                    <div class="total-bet">
                        Total Bet: <span id="total-bet">0</span> pts
                    </div>
                    <button class="btn-clear" id="btn-clear">🗑️ Clear All</button>
                </div>
            </div>
            
            <!-- Control Buttons -->
            <div class="controls">
                <button class="btn-primary" id="btn-roll">
                    ROLL DICE
                </button>
                <button class="btn-secondary" id="btn-cashout">
                    💰 Cash Out
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Loading 3D Engine...</p>
        </div>
    </div>
    
    <!-- External Libraries - Load synchronously -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- Load cannon.js from local server (hosted on your server) -->
    <script src="js/libs/cannon.min.js"></script>
    <script>
        // Verify CANNON loaded
        (function() {
            function checkCANNON() {
                if (typeof CANNON !== 'undefined') {
                    console.log('✅ CANNON.js loaded successfully (local server)');
                    window.CANNON_LOADED = true;
                    window.dispatchEvent(new CustomEvent('cannon-loaded'));
                } else {
                    console.warn('⚠️ CANNON not yet defined, retrying...');
                    setTimeout(checkCANNON, 50);
                }
            }
            // Check immediately and after a short delay
            checkCANNON();
        })();
    </script>
    
    <!-- Verify libraries loaded -->
    <script>
        window.addEventListener('load', function() {
            if (typeof THREE === 'undefined') {
                console.error('❌ THREE.js failed to load!');
            } else {
                console.log('✅ THREE.js loaded');
            }
        });
    </script>
    
    <!-- Game Modules - Load after libraries -->
    <!-- Version query forces cache refresh when config changes -->
    <script type="module" src="js/main.js?v=6.0"></script>
</body>
</html>

