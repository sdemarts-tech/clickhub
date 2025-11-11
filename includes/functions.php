<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if already logged in
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate random string
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

// Success message
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Error message
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Get and clear success message
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

// Get and clear error message
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

// Verify Turnstile captcha
function verifyTurnstile($token) {
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] ?? false;
}

// Get user by ID
function getUserById($userId) {
    global $supabase;
    $result = $supabase->select(TABLE_USERS, '*', ['id' => $userId]);
    return !empty($result) && !isset($result['error']) ? $result[0] : null;
}

// Get user by username
function getUserByUsername($username) {
    global $supabase;
    $result = $supabase->select(TABLE_USERS, '*', ['username' => $username]);
    return !empty($result) && !isset($result['error']) ? $result[0] : null;
}

// Get user by email
function getUserByEmail($email) {
    global $supabase;
    $result = $supabase->select(TABLE_USERS, '*', ['email' => $email]);
    return !empty($result) && !isset($result['error']) ? $result[0] : null;
}

// Update user points
function updateUserPoints($userId, $points) {
    global $supabase;
    $user = getUserById($userId);
    if ($user) {
        $newPoints = $user['points'] + $points;
        return $supabase->update(TABLE_USERS, ['points' => $newPoints], ['id' => $userId]);
    }
    return false;
}

// Check daily captcha limit
function checkCaptchaLimit($userId) {
    global $supabase;
    
    $today = date('Y-m-d');
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_CAPTCHA_SOLVES . '?user_id=eq.' . $userId . '&solved_at=gte.' . $today . 'T00:00:00';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    $count = count($result);
    
    return $count < DAILY_CAPTCHA_LIMIT;
}

// Get today's captcha count
function getTodayCaptchaCount($userId) {
    global $supabase;
    
    $today = date('Y-m-d');
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_CAPTCHA_SOLVES . '?user_id=eq.' . $userId . '&solved_at=gte.' . $today . 'T00:00:00';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (!is_array($result)) {
        return 0;
    }
    return count($result);
}

// ============================================
// GAME SYSTEM FUNCTIONS
// ============================================

// Get today's game plays count (across all games)
function getTodayGamePlaysCount($userId) {
    global $supabase;
    
    $today = date('Y-m-d');
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_GAME_PLAYS . '?user_id=eq.' . $userId . '&played_at=gte.' . $today . 'T00:00:00';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return count($result);
}

// Check if user can play more games today
function canPlayGameToday($userId) {
    return getTodayGamePlaysCount($userId) < DAILY_GAME_LIMIT;
}

// Get remaining game plays for today
function getRemainingGamePlays($userId) {
    $played = getTodayGamePlaysCount($userId);
    $remaining = DAILY_GAME_LIMIT - $played;
    return max(0, $remaining);
}

// Check if user can play a specific game (cooldown check)
function canPlayGame($userId, $gameId) {
    global $supabase;
    
    // Get game cooldown settings
    $gameResult = $supabase->select(TABLE_GAMES, 'play_cooldown_minutes', ['id' => $gameId]);
    if (empty($gameResult) || isset($gameResult['error'])) {
        return false;
    }
    
    $cooldownMinutes = $gameResult[0]['play_cooldown_minutes'] ?? 60;
    
    // If cooldown is 0, no cooldown - can always play
    if ($cooldownMinutes == 0) {
        return true;
    }
    
    // Get last play time for this specific game
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_GAME_PLAYS . 
           '?user_id=eq.' . $userId . 
           '&game_id=eq.' . $gameId . 
           '&order=played_at.desc&limit=1';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!is_array($result) || empty($result)) {
        return true; // Never played before
    }
    
    $lastPlayTime = strtotime($result[0]['played_at']);
    $cooldownSeconds = $cooldownMinutes * 60;
    $nextAvailableTime = $lastPlayTime + $cooldownSeconds;
    
    return time() >= $nextAvailableTime;
}

// Get today's play count for a specific game
function getTodayGamePlaysForGame($userId, $gameId) {
    global $supabase;
    
    $today = date('Y-m-d');
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_GAME_PLAYS . 
           '?user_id=eq.' . $userId . 
           '&game_id=eq.' . $gameId . 
           '&played_at=gte.' . $today . 'T00:00:00';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (!is_array($result)) {
        return 0;
    }
    return count($result);
}

// Check if user has reached per-game daily limit
function hasReachedGameDailyLimit($userId, $gameId, $maxPlaysPerDay) {
    $playsToday = getTodayGamePlaysForGame($userId, $gameId);
    return $playsToday >= $maxPlaysPerDay;
}

// Get remaining plays for a specific game today
function getRemainingGamePlaysForGame($userId, $gameId, $maxPlaysPerDay) {
    $playsToday = getTodayGamePlaysForGame($userId, $gameId);
    $remaining = $maxPlaysPerDay - $playsToday;
    return max(0, $remaining);
}

// Get time until game is available (in minutes)
function getGameCooldownRemaining($userId, $gameId) {
    global $supabase;
    
    // Get game cooldown settings
    $gameResult = $supabase->select(TABLE_GAMES, 'play_cooldown_minutes', ['id' => $gameId]);
    if (empty($gameResult) || isset($gameResult['error'])) {
        return 0;
    }
    
    $cooldownMinutes = $gameResult[0]['play_cooldown_minutes'] ?? 60;
    
    // Get last play time
    $url = SUPABASE_URL . '/rest/v1/' . TABLE_GAME_PLAYS . 
           '?user_id=eq.' . $userId . 
           '&game_id=eq.' . $gameId . 
           '&order=played_at.desc&limit=1';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!is_array($result) || empty($result)) {
        return 0; // Never played
    }
    
    $lastPlayTime = strtotime($result[0]['played_at']);
    $cooldownSeconds = $cooldownMinutes * 60;
    $nextAvailableTime = $lastPlayTime + $cooldownSeconds;
    $remainingSeconds = $nextAvailableTime - time();
    
    return max(0, ceil($remainingSeconds / 60));
}

// Get user's best score for a game
function getUserBestScore($userId, $gameId) {
    global $supabase;
    
    $url = SUPABASE_URL . '/rest/v1/game_best_scores?user_id=eq.' . $userId . '&game_id=eq.' . $gameId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return !empty($result) ? $result[0]['best_score'] : 0;
}

// Update user's best score if new score is higher
function updateBestScore($userId, $gameId, $newScore) {
    global $supabase;
    
    $currentBest = getUserBestScore($userId, $gameId);
    
    if ($newScore > $currentBest) {
        $data = [
            'user_id' => $userId,
            'game_id' => $gameId,
            'best_score' => $newScore,
            'achieved_at' => date('Y-m-d H:i:s')
        ];
        
        // Try to insert, if exists it will fail, then update
        $result = $supabase->insert('game_best_scores', $data);
        
        if (isset($result['error'])) {
            // Already exists, update instead
            $url = SUPABASE_URL . '/rest/v1/game_best_scores?user_id=eq.' . $userId . '&game_id=eq.' . $gameId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . SUPABASE_SERVICE_KEY,
                'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
                'Content-Type: application/json',
                'Prefer: return=representation'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'best_score' => $newScore,
                'achieved_at' => date('Y-m-d H:i:s')
            ]));
            
            curl_exec($ch);
            curl_close($ch);
        }
        
        return true;
    }
    
    return false;
}

// Generate unique session ID for game play
function generateGameSessionId() {
    return uniqid('session_', true) . '_' . bin2hex(random_bytes(16));
}

// Validate game session
function isValidGameSession($sessionId) {
    global $supabase;
    
    // Check if session has already been used
    $result = $supabase->select(TABLE_GAME_PLAYS, 'id', ['session_id' => $sessionId]);
    
    return empty($result) || isset($result['error']);
}

// Format time remaining
function formatTimeRemaining($minutes) {
    if ($minutes < 1) {
        return 'Available now';
    } elseif ($minutes < 60) {
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
    } else {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . 
               ($mins > 0 ? ' ' . $mins . ' min' : '');
    }
}
?>