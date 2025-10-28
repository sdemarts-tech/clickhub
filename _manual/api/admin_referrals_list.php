<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_admin();

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending','approved','rejected'])) $status='pending';

$stmt = db()->prepare("
  SELECT r.*, 
         ru.username AS referrer_username, ru.email AS referrer_email,
         eu.username AS referee_username,  eu.email AS referee_email
  FROM referrals r
  JOIN users ru ON ru.id = r.referrer_id
  JOIN users eu ON eu.id = r.referee_id
  WHERE r.status=?
  ORDER BY r.created_at DESC
  LIMIT 200
");
$stmt->execute([$status]);
json_ok(['referrals'=>$stmt->fetchAll()]);
