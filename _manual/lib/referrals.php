<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/points.php';

function find_referrer_id_by_code_or_username(string $codeOrUsername) {
  // try username first
  $stmt = db()->prepare("SELECT id FROM users WHERE username = ?");
  $stmt->execute([$codeOrUsername]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;

  // then referral_code
  $stmt = db()->prepare("SELECT id FROM users WHERE referral_code = ?");
  $stmt->execute([$codeOrUsername]);
  $id = $stmt->fetchColumn();
  return $id ? (int)$id : null;
}

function process_referral_on_user_approval(int $referee_id, int $admin_id) {
  // find pending referral for this referee
  $ref = db()->prepare("SELECT * FROM referrals WHERE referee_id=? AND status='pending' LIMIT 1");
  $ref->execute([$referee_id]);
  $row = $ref->fetch();
  if (!$row) return;

  // approve + award referrer
  $award = 20; // change if needed
  db()->beginTransaction();
  $u = db()->prepare("UPDATE referrals SET status='approved', approved_at=NOW(), approved_by=? WHERE id=?");
  $u->execute([$admin_id, $row['id']]);

  add_points((int)$row['referrer_id'], $award, 'referral_approved', "Approved referee #$referee_id");
  db()->commit();
}
