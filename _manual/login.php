<?php
$pageTitle='Log in';
include __DIR__.'/partials/header.php';
?>
<div class="narrow">
  <h1 class="page-title">Log in</h1>

  <div id="msg-box" class="msg" style="display:none;"></div>

  <form id="login-form" class="form card" method="post" action="<?= $BASE_PATH ?>/api/auth_login.php" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">

    <div class="form__row">
      <label class="form__label">Email</label>
      <input name="email" type="email" class="input" required />
    </div>

    <div class="form__row">
      <label class="form__label">Password</label>
      <input name="password" type="password" class="input" required />
    </div>

    <div class="form__actions">
      <button class="btn btn--primary" type="submit">Log in</button>
      <a class="btn btn--ghost" href="<?= $BASE_PATH ?>/signup.php">Create an account</a>
    </div>
  </form>
</div>

<script>
document.querySelector('#login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form   = e.target;
  const msgBox = document.getElementById('msg-box');
  msgBox.style.display = 'none';
  msgBox.textContent = '';

  const fd  = new FormData(form);
  const res = await fetch(form.action, { method:'POST', body: fd });

  let data;
  try { data = await res.json(); } catch (_) { data = { ok:false, error:'Server error' }; }

  msgBox.style.display   = 'block';
  msgBox.style.padding   = '10px';
  msgBox.style.borderRadius = '6px';
  msgBox.style.marginBottom = '15px';

  if (data.ok) {
    msgBox.style.background = '#122';
    msgBox.style.color = '#6f6';
    msgBox.textContent = 'Logged in. Redirecting…';
    const to = (data.redirect || '<?= $BASE_PATH ?>/dashboard.php')
                .replace(/^\/(?!~)/, '<?= $BASE_PATH ?>/');
    window.location.href = to;
  } else {
    msgBox.style.background = '#211';
    msgBox.style.color = '#f66';
    msgBox.textContent = data.error || 'Invalid email or password.';
  }
});
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
