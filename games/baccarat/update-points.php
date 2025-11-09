<?php
ob_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$rootPath = dirname(dirname(__DIR__));
$configPath = $rootPath . '/includes/config.php';
$supabasePath = $rootPath . '/includes/supabase.php';
$functionsPath = $rootPath . '/includes/functions.php';

if (!file_exists($configPath) || !file_exists($supabasePath) || !file_exists($functionsPath)) {
    echo json_encode(['success' => true, 'warning' => 'Database files missing, running in demo mode.']);
    exit;
}

require_once $configPath;
require_once $supabasePath;
require_once $functionsPath;

try {
    $roundId = $payload['round'] ?? null;
    $bets = $payload['bets'] ?? [];
    $outcome = $payload['outcome'] ?? [];
    $net = $payload['net'] ?? 0;
    $balance = $payload['balance'] ?? null;

    if (!$roundId || empty($bets) || empty($outcome)) {
        throw new Exception('Incomplete round data');
    }

    // Update user points
    if ($balance !== null) {
        $supabase->update(TABLE_USERS, ['points' => $balance], ['id' => $userId]);
        $_SESSION['points'] = $balance;
    }

    // Log round
    $roundData = [
        'round_id' => $roundId,
        'user_id' => $userId,
        'bet_banker' => $bets['banker'] ?? 0,
        'bet_player' => $bets['player'] ?? 0,
        'bet_tie' => $bets['tie'] ?? 0,
        'winner' => $outcome['winner'] ?? 'unknown',
        'player_total' => $outcome['totals']['player'] ?? 0,
        'banker_total' => $outcome['totals']['banker'] ?? 0,
        'net_change' => $net,
        'created_at' => date('c')
    ];

    $supabase->insert('baccarat_bets', $roundData);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
