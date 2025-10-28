<?php
function json_ok($data = []) {
  header('Content-Type: application/json');
  echo json_encode(['ok'=>true] + $data);
  exit;
}
function json_err($msg, $code=400) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode(['ok'=>false,'error'=>$msg]);
  exit;
}
function today_utc_range(): array {
  $start = new DateTime('now', new DateTimeZone('UTC'));
  $start->setTime(0,0,0);
  $end = clone $start; $end->modify('+1 day');
  return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}
function sanitize($s) { return trim(filter_var($s, FILTER_UNSAFE_RAW)); }
