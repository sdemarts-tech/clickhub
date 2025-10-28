<?php
// Auth & CSRF helpers (stateless CSRF). Correct base_path for /~clickhub and normal domains.
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';

/** CHANGE THIS to a random 64-hex secret */
const CLICKHUB_CSRF_SECRET = 'change_me_to_a_random_64_hex_string';

/**
 * Base path resolver:
 * - If running under a userdir like /~clickhub/... => returns "/~clickhub"
 * - Else returns directory of the script ('' when at root)
 */
function base_path(): string {
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  // Userdir (/~username/anything) -> "/~username"
  if (preg_match('#^/(~[^/]+)#', $script, $m)) {
    return $m[0];
  }
  // Fallback: directory
  $bp = str_replace('\\', '/', dirname($script));
  return ($bp === '/' ? '' : $bp);
}

/** Stateless CSRF */
function csrf_token(): string {
  $ts  = time();
  $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
  $mac = hash_hmac('sha256', $ts . '|' . $ip, CLICKHUB_CSRF_SECRET, true);
  $raw = $ts . ':' . rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
  return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}
function require_csrf(): void {
  $token = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['_csrf'] ?? '');
  if (!$token) { http_response_code(403); exit('CSRF'); }
  $raw = base64_decode(strtr($token, '-_', '+/'), true);
  if ($raw === false || strpos($raw, ':') === false) { http_response_code(403); exit('CSRF'); }
  [$ts, $macB64] = explode(':', $raw, 2);
  if (!ctype_digit($ts)) { http_response_code(403); exit('CSRF'); }
  $age = time() - (int)$ts;
  if ($age < 0 || $age > 7200) { http_response_code(403); exit('CSRF'); }
  $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
  $calc = hash_hmac('sha256', $ts . '|' . $ip, CLICKHUB_CSRF_SECRET, true);
  $calcB64 = rtrim(strtr(base64_encode($calc), '+/', '-_'), '=');
  if (!hash_equals($calcB64, $macB64)) { http_response_code(403); exit('CSRF'); }
}

/** Session helpers */
function login_user(array $u): void {
  $_SESSION['uid'] = (int)$u['id'];
  session_regenerate_id(true);
}
function logout_user(): void {
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}
function is_logged_in(): bool { return !empty($_SESSION['uid']); }

function current_user(): ?array {
  if (!is_logged_in()) return null;
  $stmt = db()->prepare("SELECT u.*, COALESCE(v.total_points,0) AS total_points
                         FROM users u
                         LEFT JOIN v_user_points v ON v.user_id=u.id
                         WHERE u.id=? LIMIT 1");
  $stmt->execute([$_SESSION['uid']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}
function is_admin(): bool {
  if (!is_logged_in()) return false;
  $s = db()->prepare("SELECT 1 FROM admins WHERE user_id=?");
  $s->execute([$_SESSION['uid']]);
  return (bool)$s->fetchColumn();
}

/** Redirects that respect /~clickhub */
function require_login(): void {
  if (!is_logged_in()) { header('Location: ' . base_path() . '/login.php'); exit; }
}
function require_admin(): void {
  if (!is_admin()) { http_response_code(403); exit('Admins only'); }
}
