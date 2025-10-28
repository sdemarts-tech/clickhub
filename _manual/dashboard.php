<?php require_once __DIR__.'/lib/auth.php'; require_login(); $pageTitle='Dashboard'; include __DIR__.'/partials/header.php'; ?>
<section class="grid-2">
  <div class="card">
    <h2 class="card__title">Your Points</h2>
    <div class="stat"><span class="stat__value" id="points-total">0</span><span class="stat__label">total points</span></div>
    <div class="divider"></div>
    <h3 class="card__subtitle">Your referral link</h3>
    <div class="ref-row">
      <input class="input input--mono" id="ref-link" type="text" readonly />
      <button class="btn btn--outline" id="btn-copy-link">Copy</button>
    </div>
  </div>
  <div class="card">
    <h2 class="card__title">Daily actions</h2>
    <div class="flexcol">
      <button class="btn btn--primary" id="btn-claim-login">Claim daily login</button>
      <div class="captcha-box">[ CAPTCHA ]</div>
      <button class="btn btn--primary" id="btn-claim-captcha">Verify & claim captcha</button>
    </div>
    <p class="muted" id="claim-status"></p>
  </div>
</section>

<section class="card">
  <h2 class="card__title">Recent activity</h2>
  <table class="table" id="activity-table">
    <thead><tr><th>Date</th><th>Reason</th><th>Points</th><th>Note</th></tr></thead>
    <tbody></tbody>
  </table>
</section>
<?php include __DIR__.'/partials/footer.php'; ?>
