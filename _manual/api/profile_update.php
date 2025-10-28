<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
if (!is_logged_in()) json_err('Not logged in', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only');
require_csrf();

$display = sanitize($_POST['display_name'] ?? '');
$phone   = sanitize($_POST['phone'] ?? '');
$country = sanitize($_POST['country'] ?? '');
$username= sanitize($_POST['username'] ?? ''); // optional

$u = current_user();
try {
  $stmt = db()->prepare("UPDATE users SET display_name=?, phone=?, country=?, username=? WHERE id=?");
  $stmt->execute([$display ?: null, $phone ?: null, $country ?: null, $username ?: null, $u['id']]);
  json_ok(['message'=>'Profile updated']);
} catch (PDOException $e) {
  json_err('Update failed: '.$e->getMessage());
}
