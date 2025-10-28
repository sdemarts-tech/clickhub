<?php
require_once __DIR__ . '/points.php';

function claim_daily_login(int $user_id) {
  if (has_reason_today($user_id, 'login_daily')) return [false,'Already claimed today'];
  $pts = task_points('login_daily') ?: 1;
  add_points($user_id, $pts, 'login_daily', 'Daily login');
  record_attempt($user_id, 'login_daily', 'granted');
  return [true, $pts];
}

function claim_captcha_daily(int $user_id) {
  if (has_reason_today($user_id, 'captcha_daily')) return [false,'Already claimed today'];
  $pts = task_points('captcha_daily') ?: 5;
  add_points($user_id, $pts, 'captcha_daily', 'Daily captcha');
  record_attempt($user_id, 'captcha_daily', 'granted');
  return [true, $pts];
}
