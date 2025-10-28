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
    return count($result);
}
?>