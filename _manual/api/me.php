<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';

if (!is_logged_in()) json_err('Not logged in', 401);
$u = current_user();
if (!$u) json_err('User not found', 404);

// recent ledger
$stmt = db()->prepare("SELECT created_at, reason, points, note FROM points_ledger WHERE user_id=? ORDER BY id DESC LIMIT 10");
$stmt->execute([$u['id']]);
$recent = $stmt->fetchAll();

// flags
require_once __DIR__ . '/../lib/points.php';
$did_login   = has_reason_today($u['id'], 'login_daily');
$did_captcha = has_reason_today($u['id'], 'captcha_daily');

json_ok([
  'user'=>[
    'id'=>$u['id'],
    'email'=>$u['email'],
    'username'=>$u['username'],
    'display_name'=>$u['display_name'],
    'phone'=>$u['phone'],
    'country'=>$u['country'],
    'referral_code'=>$u['referral_code'],
    'status'=>$u['status'],
    'total_points'=>(int)$u['total_points']
  ],
  'recent_ledger'=>$recent,
  'flags'=>['did_login_today'=>$did_login,'did_captcha_today'=>$did_captcha]
]);
