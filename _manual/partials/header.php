<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../lib/auth.php';
$SITE_TITLE = 'ClickHub';
$BASE_PATH  = '/~clickhub'; // set to '' if at domain root
$csrf = csrf_token();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' — ' : '' ?><?= $SITE_TITLE ?></title>
  <link rel="stylesheet" href="<?= $BASE_PATH ?>/style.css" />
  <link rel="icon" href="<?= $BASE_PATH ?>/assets/favicon.png" />
  <script>window.BASE_PATH="<?= $BASE_PATH ?>"; window.CSRF="<?= $csrf ?>";</script>
  <script src="<?= $BASE_PATH ?>/app.js" defer></script>
</head>
<body class="theme-dark">
<header class="site-header">
  <a href="<?= $BASE_PATH ?>/index.php" class="brand">
    <img src="<?= $BASE_PATH ?>/assets/logo.png" alt="ClickHub" class="brand__logo" />
    <span class="brand__name">ClickHub</span>
  </a>
  <nav class="nav">
    <?php if (is_logged_in()) : ?>
      <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/dashboard.php">Dashboard</a>
      <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/profile.php">Profile</a>
      <?php if (is_admin()) : ?>
        <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/admin.php">Admin</a>
      <?php endif; ?>
      <a class="btn btn--primary" href="<?= $BASE_PATH ?>/api/auth_logout.php">Log out</a>
    <?php else: ?>
      <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/login.php">Log in</a>
      <a class="btn btn--primary" href="<?= $BASE_PATH ?>/signup.php">Sign up</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
