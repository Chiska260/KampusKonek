<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$email = strtolower(post('email'));

if (is_post()) {
    csrf_check();
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (email_kind($email) === null) {
        $errors[] = domain_hint();
    } else {
        $user = find_user_by_email($email);
        if (!$user) {
            // no account yet -> straight to sign up
            flash('info', 'We couldn\'t find an account for that email. Sign up with your school email to get started.');
            redirect('register.php?email=' . urlencode($email));
        }
        if (!password_verify($password, $user['password_hash'])) {
            $errors[] = 'Wrong password. Try again, or ask the system admin to reset it.';
        } elseif (!$user['is_active']) {
            $errors[] = 'This account is deactivated. Contact the system admin.';
        } else {
            login_user((int) $user['id']);
            redirect('dashboard.php');
        }
    }
}

render_public_header('Log in');
?>
<div class="auth-card">
  <h1>Welcome back</h1>
  <p class="sub">Log in with your school email.</p>
  <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" novalidate data-email-form>
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">School email</label>
      <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" placeholder="20231234@students.nu-laguna.edu.ph" data-domain-check>
      <p class="field-hint" data-domain-hint><?= e(domain_hint()) ?></p>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary block" type="submit">Log in</button>
  </form>
  <p class="switch">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div>
<?php render_public_footer();
