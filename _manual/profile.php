<?php require_once __DIR__.'/lib/auth.php'; require_login(); $u=current_user(); $pageTitle='Profile'; include __DIR__.'/partials/header.php'; ?>
<div class="narrow">
  <h1 class="page-title">Your profile</h1>
  <form id="profile-form" class="form card" method="post" action="<?= $BASE_PATH ?>/api/profile_update.php">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form__row">
      <label class="form__label">Email</label>
      <input class="input" value="<?= htmlspecialchars($u['email']) ?>" readonly />
    </div>
    <div class="form__row">
      <label class="form__label">Username</label>
      <input name="username" class="input" value="<?= htmlspecialchars($u['username']) ?>" />
    </div>
    <div class="form__row">
      <label class="form__label">Display name</label>
      <input name="display_name" class="input" value="<?= htmlspecialchars($u['display_name']) ?>" />
    </div>
    <div class="form__row grid-2">
      <div>
        <label class="form__label">Phone</label>
        <input name="phone" class="input" value="<?= htmlspecialchars($u['phone']) ?>" />
      </div>
      <div>
        <label class="form__label">Country</label>
        <input name="country" class="input" value="<?= htmlspecialchars($u['country']) ?>" />
      </div>
    </div>
    <div class="form__actions">
      <button class="btn btn--primary">Save</button>
    </div>
  </form>

  <h2 class="page-title">Change password</h2>
  <form id="password-form" class="form card" method="post" action="<?= $BASE_PATH ?>/api/change_password.php">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="form__row"><label class="form__label">Current password</label><input name="current_password" type="password" class="input" required></div>
    <div class="form__row"><label class="form__label">New password</label><input name="new_password" type="password" class="input" minlength="6" required></div>
    <div class="form__actions"><button class="btn btn--primary">Change</button></div>
  </form>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
