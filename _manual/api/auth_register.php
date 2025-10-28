<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/referrals.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only', 405);
require_csrf();

$email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password  = $_POST['password'] ?? '';
$username  = trim($_POST['username'] ?? '');
$display   = trim($_POST['display_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$country   = trim($_POST['country'] ?? '');
$ref_code  = trim($_POST['ref_code'] ?? '');

if (!$email || strlen($password) < 6) json_err('Invalid email or password');
if ($username === '') $username = null;

// resolve referrer (by username first, then referral_code)
$referrer_id = null;
if ($ref_code !== '') {
  $rid = find_referrer_id_by_code_or_username($ref_code);
  if ($rid) $referrer_id = $rid;
}

try {
  // create pending user
  $stmt = db()->prepare("INSERT INTO users
    (email, password_hash, username, display_name, phone, country, referred_by_user_id, status)
    VALUES (?,?,?,?,?,?,?, 'pending')");
  $stmt->execute([
    $email,
    password_hash($password, PASSWORD_BCRYPT),
    $username,
    $display ?: null,
    $phone ?: null,
    $country ?: null,
    $referrer_id
  ]);
  $uid = (int)db()->lastInsertId();

  // if referred, create pending referral
  if ($referrer_id) {
    $r = db()->prepare("INSERT INTO referrals (referrer_id, referee_id, status, first_click_at, first_click_ip)
                        VALUES (?, ?, 'pending', NOW(), ?)");
    $r->execute([$referrer_id, $uid, $_SERVER['REMOTE_ADDR'] ?? null]);
  }

  json_ok(['message'=>'Registration received. Waiting for admin approval.', 'user_id'=>$uid]);
} catch (PDOException $e) {
  json_err('Registration failed: '.$e->getMessage(), 400);
}
