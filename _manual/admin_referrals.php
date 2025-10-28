<?php require_once __DIR__.'/lib/auth.php'; require_admin(); $pageTitle='Admin — Referrals'; include __DIR__.'/partials/header.php'; ?>
<h1 class="page-title">Referrals</h1>
<?php include __DIR__.'/partials/admin_nav.php'; ?>
<div class="tabs">
  <button class="tab tab--active" data-tab="pending">Pending</button>
  <button class="tab" data-tab="approved">Approved</button>
  <button class="tab" data-tab="rejected">Rejected</button>
</div>
<div class="tabpanels">
  <div class="tabpanel tabpanel--active" id="tab-pending"><table class="table" id="ref-pending"><thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th><th>Actions</th></tr></thead><tbody></tbody></table></div>
  <div class="tabpanel" id="tab-approved"><table class="table" id="ref-approved"><thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th></tr></thead><tbody></tbody></table></div>
  <div class="tabpanel" id="tab-rejected"><table class="table" id="ref-rejected"><thead><tr><th>Date</th><th>Referrer</th><th>Referee</th><th>Notes</th></tr></thead><tbody></tbody></table></div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
