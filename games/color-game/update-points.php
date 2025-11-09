<?php
// Enable output buffering to catch any errors/warnings before JSON output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress display of errors (but still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in (simple check)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Try to load includes for database access (optional)
$rootPath = dirname(dirname(__DIR__));
$configPath = $rootPath . '/includes/config.php';
$supabasePath = $rootPath . '/includes/supabase.php';
$functionsPath = $rootPath . '/includes/functions.php';

$hasDatabase = false;
if (file_exists($configPath) && file_exists($supabasePath) && file_exists($functionsPath)) {
    try {
        // Suppress session ini_set warnings since session is already active
        $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING);
        
        require_once $configPath;
        require_once $supabasePath;
        require_once $functionsPath;
        
        // Restore error reporting
        error_reporting($oldErrorReporting);
        
        $hasDatabase = true;
    } catch (Exception $e) {
        // Restore error reporting if exception occurred
        error_reporting($oldErrorReporting ?? E_ALL);
        // Database files exist but failed to load
        error_log("Update Points Error: " . $e->getMessage());
    }
}

// Handle both JSON (from fetch) and FormData (from sendBeacon)
$input = file_get_contents('php://input');

// Try JSON first (from fetch requests)
$data = json_decode($input, true);

// If JSON decode failed or empty, try form data (from sendBeacon)
if (($data === null || empty($data)) && !empty($_POST)) {
    $data = $_POST;
} elseif (($data === null || empty($data)) && !empty($input)) {
    // Try parsing as form-encoded string
    parse_str($input, $data);
}

$newBalance = isset($data['balance']) ? (int)$data['balance'] : null;

if ($newBalance === null || $newBalance < 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid balance']);
    exit;
}

// Update user points
$userId = $_SESSION['user_id'];

// If database is available, update it
if ($hasDatabase && function_exists('updateUserPoints')) {
    try {
        $user = function_exists('getUserById') ? getUserById($userId) : null;
        if ($user) {
            $currentPoints = $user['points'] ?? 0;
            $difference = $newBalance - $currentPoints;
            $result = updateUserPoints($userId, $difference);
            if ($result && !isset($result['error'])) {
                // Also update session
                $_SESSION['points'] = $newBalance;
                ob_clean();
                echo json_encode(['success' => true, 'balance' => $newBalance]);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Update Points DB Error: " . $e->getMessage());
    }
}

// Fallback: Update session only (database will sync later)
$_SESSION['points'] = $newBalance;

// Clear any output buffer and send clean JSON
ob_clean();
echo json_encode(['success' => true, 'balance' => $newBalance, 'note' => 'Updated in session only']);
exit;
?>
