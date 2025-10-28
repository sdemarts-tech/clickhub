<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/points.php';
require_admin();
require_csrf();

$user_id = (int)($_POST['user_id'] ?? 0);
$delta   = (int)($_POST['points'] ?? 0);
$note    = substr($_POST['note'] ?? '', 0, 255);

if (!$user_id || !$delta) json_err('user_id and points required');

add_points($user_id, $delta, 'manual_adjust', $note ?: 'manual');
json_ok(['message'=>'Points adjusted']);
