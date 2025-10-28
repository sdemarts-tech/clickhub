<?php $pageTitle='Earn Points'; include __DIR__.'/partials/header.php'; ?>
<section class="hero">
  <h1 class="hero__title">Earn points with simple daily tasks & referrals.</h1>
  <p class="hero__subtitle">Quick actions. Fair rewards. Your link, your progress.</p>
  <div class="hero__cta">
    <a class="btn btn--primary" href="<?= $BASE_PATH ?>/signup.php">Get started</a>
    <a class="btn btn--outline" href="<?= $BASE_PATH ?>/dashboard.php">Go to dashboard</a>
  </div>
</section>
<section class="features grid-3">
  <div class="card"><h3 class="card__title">Admin Approval</h3><p class="card__text">New accounts require admin approval.</p></div>
  <div class="card"><h3 class="card__title">Daily Check-in</h3><p class="card__text">Login & captcha rewards once per day.</p></div>
  <div class="card"><h3 class="card__title">Referrals</h3><p class="card__text">Invite via your username link.</p></div>
</section>
<?php include __DIR__.'/partials/footer.php'; ?>
