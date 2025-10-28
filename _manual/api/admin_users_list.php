<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_admin();

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending','active','suspended'])) $status='pending';

$stmt = db()->prepare("SELECT u.id,u.email,u.username,u.display_name,u.phone,u.country,u.status,u.created_at,COALESCE(v.total_points,0) AS total_points
                       FROM users u LEFT JOIN v_user_points v ON v.user_id=u.id
                       WHERE u.status=? ORDER BY u.created_at DESC LIMIT 200");
$stmt->execute([$status]);
json_ok(['users'=>$stmt->fetchAll()]);
