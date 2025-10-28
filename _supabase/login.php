<?php $pageTitle = 'Log in'; include __DIR__.'/partials/header.php'; ?>
  <div class="narrow">
    <h1 class="page-title">Welcome back</h1>
    <form id="login-form" class="form card" autocomplete="off">
      <div class="form__row">
        <label for="login-email" class="form__label">Email</label>
        <input id="login-email" name="email" type="email" class="input" placeholder="you@email.com" required />
      </div>
      <div class="form__row">
        <label for="login-password" class="form__label">Password</label>
        <input id="login-password" name="password" type="password" class="input" placeholder="••••••••" required />
      </div>
      <div class="form__actions">
        <button type="submit" class="btn btn--primary" id="btn-login">Log in</button>
        <a href="signup.php" class="btn btn--ghost">Create account</a>
      </div>
    </form>
  </div>
<?php include __DIR__.'/partials/footer.php'; ?>
