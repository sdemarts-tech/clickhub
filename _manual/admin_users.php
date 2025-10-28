<?php require_once __DIR__.'/lib/auth.php'; require_admin(); $pageTitle='Admin — Users'; include __DIR__.'/partials/header.php'; ?>
<h1 class="page-title">Users</h1>
<?php include __DIR__.'/partials/admin_nav.php'; ?>
<div class="tabs">
  <button class="tab tab--active" data-tab="pending">Pending</button>
  <button class="tab" data-tab="active">Active</button>
  <button class="tab" data-tab="suspended">Suspended</button>
</div>
<div class="tabpanels">
  <div class="tabpanel tabpanel--active" id="tab-pending"><table class="table" id="users-pending"><thead><tr><th>Email</th><th>Username</th><th>Points</th><th>Actions</th></tr></thead><tbody></tbody></table></div>
  <div class="tabpanel" id="tab-active"><table class="table" id="users-active"><thead><tr><th>Email</th><th>Username</th><th>Points</th><th>Actions</th></tr></thead><tbody></tbody></table></div>
  <div class="tabpanel" id="tab-suspended"><table class="table" id="users-suspended"><thead><tr><th>Email</th><th>Username</th><th>Points</th><th>Actions</th></tr></thead><tbody></tbody></table></div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
