<?php require_once __DIR__.'/lib/auth.php'; require_admin(); $pageTitle='Admin — Points'; include __DIR__.'/partials/header.php'; ?>
<h1 class="page-title">Points Adjust</h1>
<?php include __DIR__.'/partials/admin_nav.php'; ?>
<div class="card narrow">
  <form id="points-adjust-form" class="form">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form__row"><label class="form__label">User ID</label><input name="user_id" class="input" required></div>
    <div class="form__row"><label class="form__label">Points (+/-)</label><input name="points" class="input" required></div>
    <div class="form__row"><label class="form__label">Note</label><input name="note" class="input"></div>
    <div class="form__actions"><button class="btn btn--primary" id="btn-adjust">Apply</button></div>
  </form>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
