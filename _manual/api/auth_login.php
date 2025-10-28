<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only');
require_csrf();

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$pass  = $_POST['password'] ?? '';
if (!$email || !$pass) json_err('Invalid credentials');

$stmt = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
  json_err('Wrong email or password', 401);
}

if (($user['status'] ?? '') !== 'active') {
  json_err('Account pending approval. Please wait.', 403);
}

login_user($user);

$bp = base_path();               // e.g. '/~clickhub' or ''
json_ok(['redirect' => $bp . '/dashboard.php']);
