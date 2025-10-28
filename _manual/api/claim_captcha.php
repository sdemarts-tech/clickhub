<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/tasks.php';

if (!is_logged_in()) json_err('Not logged in', 401);
require_csrf();

/* TODO: verify real captcha server-side (Turnstile/recaptcha).
   For MVP we assume front-end validation. */

[$ok,$msgOrPts] = claim_captcha_daily($_SESSION['uid']);
if (!$ok) json_err($msgOrPts, 400);
json_ok(['points_added'=>$msgOrPts]);
