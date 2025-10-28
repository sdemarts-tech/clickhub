<?php
  // simple site constants (edit if you move files)
  $SITE_TITLE = 'ClickHub';
  $BASE_PATH  = '/~clickhub'
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($pageTitle) ? $pageTitle.' — ' : '' ?><?= $SITE_TITLE ?></title>
  <link rel="stylesheet" href="<?= $BASE_PATH ?>/style.css" />
  <link rel="icon" href="<?= $BASE_PATH ?>/assets/logo.png" />
  

  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script src="<?= $BASE_PATH ?>/supabase.js"></script>
  <!-- App logic -->
  <script src="<?= $BASE_PATH ?>/app.js" defer></script>

  
</head>
<body class="theme-dark">
  <header class="site-header">
    <a href="<?= $BASE_PATH ?>/index.php" class="brand">
      <img src="<?= $BASE_PATH ?>/assets/logo.png" alt="ClickHub" class="brand__logo" />
      <span class="brand__name">ClickHub</span>
    </a>
    <nav class="nav">
      <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/login.php">Log in</a>
      <a class="btn btn--primary" href="<?= $BASE_PATH ?>/signup.php">Sign up</a>
    </nav>
  </header>
  <main class="container">
