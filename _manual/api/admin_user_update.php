<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/referrals.php';
require_admin();
require_csrf();

$action = $_POST['action'] ?? '';
$user_id = (int)($_POST['user_id'] ?? 0);
if (!$user_id) json_err('user_id required');

if ($action === 'approve') {
  $u = db()->prepare("UPDATE users SET status='active', approved_at=NOW(), approved_by=? WHERE id=?");
  $u->execute([$_SESSION['uid'], $user_id]);

  // award referral if exists
  process_referral_on_user_approval($user_id, $_SESSION['uid']);
  json_ok(['message'=>'User approved']);
}
elseif ($action === 'suspend') {
  $u = db()->prepare("UPDATE users SET status='suspended' WHERE id=?");
  $u->execute([$user_id]);
  json_ok(['message'=>'User suspended']);
}
else {
  // edit basic fields (admin)
  $display = sanitize($_POST['display_name'] ?? '');
  $phone   = sanitize($_POST['phone'] ?? '');
  $country = sanitize($_POST['country'] ?? '');
  $username= sanitize($_POST['username'] ?? '');
  $st      = $_POST['status'] ?? null;
  if ($st && !in_array($st,['pending','active','suspended'])) $st=null;

  $q = "UPDATE users SET display_name=?, phone=?, country=?, username=?".($st?", status=?":"")." WHERE id=?";
  $params = [$display ?: null, $phone ?: null, $country ?: null, $username ?: null];
  if ($st) $params[] = $st;
  $params[] = $user_id;
  $stmt = db()->prepare($q); $stmt->execute($params);
  json_ok(['message'=>'User updated']);
}
