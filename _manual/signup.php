<?php
$pageTitle='Sign up';
include __DIR__.'/partials/header.php';
?>

<div class="narrow">
  <h1 class="page-title">Create your account</h1>

  <div id="msg-box" class="msg" style="display:none;"></div>

  <form id="signup-form" class="form card" method="post" action="<?= $BASE_PATH ?>/api/auth_register.php" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">

    <div class="form__row">
      <label class="form__label">Referral (username or code)</label>
      <input name="ref_code" id="ref_code" class="input" type="text" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>" readonly>
    </div>

    <div class="form__row">
      <label class="form__label">Email</label>
      <input name="email" type="email" class="input" required />
    </div>

    <div class="form__row">
      <label class="form__label">Password</label>
      <input name="password" type="password" class="input" minlength="6" required />
    </div>

    <div class="form__row">
      <label class="form__label">Username (optional)</label>
      <input name="username" type="text" class="input" placeholder="yourname" />
    </div>

    <div class="form__row grid-2">
      <div>
        <label class="form__label">Display name</label>
        <input name="display_name" type="text" class="input" />
      </div>
      <div>
        <label class="form__label">Phone</label>
        <input name="phone" type="text" class="input" />
      </div>
    </div>

    <div class="form__row">
      <label class="form__label">Country</label>
      <input name="country" type="text" class="input" />
    </div>

    <div class="form__actions">
      <button class="btn btn--primary" type="submit">Sign up</button>
      <a href="<?= $BASE_PATH ?>/login.php" class="btn btn--ghost">Have an account? Log in</a>
    </div>
    <p class="form__hint">After signup, an admin will approve your account.</p>
  </form>
</div>

<script>
document.querySelector('#signup-form').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const msgBox = document.getElementById('msg-box');
  msgBox.style.display = 'none';
  msgBox.textContent = '';

  const fd = new FormData(form);
  const res = await fetch(form.action, {
    method: 'POST',
    body: fd
  });
  const data = await res.json();

  msgBox.style.display = 'block';
  msgBox.style.padding = '10px';
  msgBox.style.borderRadius = '6px';
  msgBox.style.marginBottom = '15px';

  if (data.ok) {
    msgBox.style.background = '#122';
    msgBox.style.color = '#6f6';
    msgBox.textContent = data.message || 'Registered successfully. Waiting for admin approval.';
    form.reset();
  } else {
    msgBox.style.background = '#211';
    msgBox.style.color = '#f66';
    msgBox.textContent = data.error || 'Error submitting form.';
  }
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
