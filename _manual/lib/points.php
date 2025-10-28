<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/util.php';

function add_points(int $user_id, int $points, string $reason, string $note='') {
  $stmt = db()->prepare("INSERT INTO points_ledger (user_id, points, reason, note) VALUES (?,?,?,?)");
  $stmt->execute([$user_id, $points, $reason, $note]);
}
function total_points(int $user_id): int {
  $stmt = db()->prepare("SELECT COALESCE(SUM(points),0) FROM points_ledger WHERE user_id=?");
  $stmt->execute([$user_id]); return (int)$stmt->fetchColumn();
}
function has_reason_today(int $user_id, string $reason): bool {
  [$start,$end] = today_utc_range();
  $stmt = db()->prepare("SELECT 1 FROM points_ledger WHERE user_id=? AND reason=? AND created_at>=? AND created_at<? LIMIT 1");
  $stmt->execute([$user_id,$reason,$start,$end]);
  return (bool)$stmt->fetchColumn();
}
function record_attempt(int $user_id, string $task_code, string $status='granted') {
  $stmt = db()->prepare("INSERT INTO task_attempts (user_id, task_code, status, ip, ua) VALUES (?,?,?,?,?)");
  $stmt->execute([$user_id, $task_code, $status, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
}
function task_points(string $task_code): int {
  $stmt = db()->prepare("SELECT points FROM tasks WHERE task_code=?");
  $stmt->execute([$task_code]);
  return (int)($stmt->fetchColumn() ?: 0);
}
