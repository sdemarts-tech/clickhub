<?php
ob_start();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['balance'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$newBalance = (int)$payload['balance'];
if ($newBalance < 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid balance']);
    exit;
}

$roundId = $payload['round'] ?? null;
$bets = $payload['bets'] ?? [];
$outcome = $payload['outcome'] ?? [];
$net = $payload['net'] ?? 0;

$rootPath = dirname(dirname(__DIR__));
$configPath = $rootPath . '/includes/config.php';
$supabasePath = $rootPath . '/includes/supabase.php';
$functionsPath = $rootPath . '/includes/functions.php';

$hasDatabase = false;
if (file_exists($configPath) && file_exists($supabasePath) && file_exists($functionsPath)) {
    try {
        $oldReporting = error_reporting(E_ALL & ~E_WARNING);
        require_once $configPath;
        require_once $supabasePath;
        require_once $functionsPath;
        error_reporting($oldReporting);
        $hasDatabase = true;
    } catch (Exception $e) {
        error_log('Baccarat update points error: ' . $e->getMessage());
    }
}

if ($hasDatabase && function_exists('getUserById') && function_exists('updateUserPoints')) {
    try {
        global $supabase;
        $user = getUserById($userId);
        if ($user) {
            $difference = $newBalance - ($user['points'] ?? 0);
            updateUserPoints($userId, $difference);
        }

        if ($roundId) {
            $record = [
                'round_id' => $roundId,
                'user_id' => $userId,
                'bet_banker' => $bets['banker'] ?? 0,
                'bet_player' => $bets['player'] ?? 0,
                'bet_tie' => $bets['tie'] ?? 0,
                'winner' => $outcome['winner'] ?? null,
                'player_total' => $outcome['totals']['player'] ?? null,
                'banker_total' => $outcome['totals']['banker'] ?? null,
                'net_change' => $net
            ];
            $supabase->insert('baccarat_bets', $record);
        }
    } catch (Exception $e) {
        error_log('Baccarat DB sync error: ' . $e->getMessage());
    }
}

$_SESSION['points'] = $newBalance;
ob_clean();
echo json_encode(['success' => true, 'balance' => $newBalance]);
exit;
