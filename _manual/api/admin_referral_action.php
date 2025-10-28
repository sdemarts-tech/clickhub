<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/points.php';
require_admin();
require_csrf();

$referral_id = (int)($_POST['referral_id'] ?? 0);
$action = $_POST['action'] ?? '';
$notes  = substr($_POST['notes'] ?? '', 0, 255);

if (!$referral_id || !in_array($action, ['approve','reject'])) json_err('Invalid');

$stmt = db()->prepare("SELECT * FROM referrals WHERE id=?");
$stmt->execute([$referral_id]);
$ref = $stmt->fetch();
if (!$ref) json_err('Not found',404);

if ($action === 'approve') {
  db()->beginTransaction();
  $u = db()->prepare("UPDATE referrals SET status='approved', notes=?, approved_at=NOW(), approved_by=? WHERE id=?");
  $u->execute([$notes ?: null, $_SESSION['uid'], $referral_id]);
  // award referrer
  add_points((int)$ref['referrer_id'], 20, 'referral_approved', "Manual approve #$referral_id");
  db()->commit();
  json_ok(['message'=>'Referral approved (+20 to referrer)']);
} else {
  $u = db()->prepare("UPDATE referrals SET status='rejected', notes=?, approved_at=NOW(), approved_by=? WHERE id=?");
  $u->execute([$notes ?: null, $_SESSION['uid'], $referral_id]);
  json_ok(['message'=>'Referral rejected']);
}
