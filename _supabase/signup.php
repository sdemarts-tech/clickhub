<?php $pageTitle = 'Sign up'; include __DIR__.'/partials/header.php'; ?>
  <div class="narrow">
    <h1 class="page-title">Create your account</h1>
    <form id="signup-form" class="form card" autocomplete="off">
      <div class="form__row">
        <label for="username" class="form__label">Username</label>
        <input id="username" name="username" type="text" class="input" placeholder="yourname" required />
      </div>
      <div class="form__row">
        <label for="email" class="form__label">Email</label>
        <input id="email" name="email" type="email" class="input" placeholder="you@email.com" required />
      </div>
      <div class="form__row">
        <label for="password" class="form__label">Password</label>
        <input id="password" name="password" type="password" class="input" placeholder="••••••••" required />
      </div>
      <input id="ref-code" name="ref_code" type="hidden" />
      <div class="form__actions">
        <button type="submit" class="btn btn--primary" id="btn-signup">Sign up</button>
        <a href="login.php" class="btn btn--ghost">Have an account? Log in</a>
      </div>
      <p class="form__hint" id="signup-hint">By signing up, you agree to our simple rules.</p>
    </form>
  </div>
<?php include __DIR__.'/partials/footer.php'; ?>
