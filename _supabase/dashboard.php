<?php $pageTitle = 'Dashboard'; include __DIR__.'/partials/header.php'; ?>
  <section class="grid-2">
    <div class="card">
      <h2 class="card__title">Your Points</h2>
      <div class="stat">
        <span class="stat__value" id="points-total">0</span>
        <span class="stat__label">total points</span>
      </div>
      <div class="divider"></div>
      <h3 class="card__subtitle">Your referral link</h3>
      <div class="ref-row">
        <input class="input input--mono" id="ref-link" type="text" readonly value="" />
        <button class="btn btn--outline" id="btn-copy-link">Copy</button>
      </div>
    </div>

    <div class="card">
      <h2 class="card__title">Daily Check-in</h2>
      <p class="muted">Verify you’re human to claim <strong>+5</strong> points (once per day).</p>
      <div id="captcha-box" class="captcha-box">[ CAPTCHA placeholder ]</div>
      <button class="btn btn--primary" id="btn-claim">Verify & Claim</button>
      <p class="muted" id="claim-status"></p>
    </div>
  </section>

  <section class="card">
    <h2 class="card__title">Recent activity</h2>
    <table class="table" id="activity-table">
      <thead><tr><th>Date</th><th>Reason</th><th>Points</th></tr></thead>
      <tbody></tbody>
    </table>
  </section>

  <section class="card" id="admin-shortcut" hidden>
    <h2 class="card__title">Admin</h2>
    <a class="btn btn--ghost" href="admin.php">Open admin panel</a>
  </section>
<?php include __DIR__.'/partials/footer.php'; ?>
