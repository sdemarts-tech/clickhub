<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
if (!is_logged_in()) json_err('Not logged in', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only');
require_csrf();

$curr = $_POST['current_password'] ?? '';
$new  = $_POST['new_password'] ?? '';
if (strlen($new) < 6) json_err('Password too short');

$u = current_user();
$stmt = db()->prepare("SELECT password_hash FROM users WHERE id=?");
$stmt->execute([$u['id']]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($curr, $hash)) json_err('Wrong current password', 403);

$upd = db()->prepare("UPDATE users SET password_hash=? WHERE id=?");
$upd->execute([password_hash($new, PASSWORD_BCRYPT), $u['id']]);

json_ok(['message'=>'Password changed']);
