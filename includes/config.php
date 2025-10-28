<?php
// ============================================
// CONFIGURATION FILE
// ============================================
// Replace the placeholder values with your actual credentials

// Supabase Configuration
define('SUPABASE_URL', 'https://hmxeifpbfpzsjdnicejm.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhteGVpZnBiZnB6c2pkbmljZWptIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjE1ODM4NTgsImV4cCI6MjA3NzE1OTg1OH0.qS1Q_txeEQTusJRXeESG6OM5_ks5wZ-OJICynh3q_Sg');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhteGVpZnBiZnB6c2pkbmljZWptIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTU4Mzg1OCwiZXhwIjoyMDc3MTU5ODU4fQ.nlsXvPjVeeAOHJ8f5Mh4J4ZQ0RKGZaOHitQFKCfZELA');

// Cloudflare Turnstile Configuration
define('TURNSTILE_SITE_KEY', '0x4AAAAAAB8-DaQmkt8kTRW5');
define('TURNSTILE_SECRET_KEY', '0x4AAAAAAB8-DduYX7aeqggnG_G6Ak7pNs4');

// Application Settings
define('SITE_NAME', 'Game Rewards');
define('SITE_URL', 'https://101.99.90.116/~clickhub/');

// Points Configuration
define('POINTS_PER_GAME', 10);
define('POINTS_PER_CAPTCHA', 5);
define('POINTS_REFERRAL_COMMISSION', 50);
define('DAILY_CAPTCHA_LIMIT', 20);
define('DAILY_GAME_LIMIT', 10);

// Database Tables
define('TABLE_USERS', 'users');
define('TABLE_GAMES', 'games');
define('TABLE_GAME_PLAYS', 'game_plays');
define('TABLE_CAPTCHA_SOLVES', 'captcha_solves');
define('TABLE_REFERRALS', 'referrals');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>