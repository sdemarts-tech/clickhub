<?php $pageTitle = 'Earn Points'; include __DIR__.'/partials/header.php'; ?>
  <section class="hero">
    <h1 class="hero__title">Earn points with simple daily tasks & referrals.</h1>
    <p class="hero__subtitle">Quick actions. Fair rewards. Your link, your progress.</p>
    <div class="hero__cta">
      <a class="btn btn--primary" href="signup.php">Get started</a>
      <a class="btn btn--outline" href="dashboard.php">Go to dashboard</a>
    </div>
  </section>

  <section class="features grid-3">
    <div class="card"><h3 class="card__title">Daily Check-in</h3><p class="card__text">Verify a simple CAPTCHA once per day to earn points.</p></div>
    <div class="card"><h3 class="card__title">Invite Friends</h3><p class="card__text">Share your referral link. You approve, we award.</p></div>
    <div class="card"><h3 class="card__title">Simple & Fast</h3><p class="card__text">No apps to install. Pure web, mobile-friendly.</p></div>
  </section>
<?php include __DIR__.'/partials/footer.php'; ?>
