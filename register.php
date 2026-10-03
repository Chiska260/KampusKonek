<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$name  = post('full_name');
$email = strtolower(post('email', get('email')));

if (is_post()) {
    csrf_check();
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password2'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'Enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (email_kind($email) === null) {
        $errors[] = domain_hint();
    } elseif (find_user_by_email($email)) {
        $errors[] = 'That email already has an account. Log in instead.';
    }
    if ($err = check_password_rules($pw, $pw2)) {
        $errors[] = $err;
    }

    if (!$errors) {
        $id = db_insert(
            'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)',
            [$name, $email, password_hash($pw, PASSWORD_DEFAULT), default_role_for($email)]
        );
        login_user($id);
        flash('success', 'Account created. Welcome to KampusKonek!');
        redirect('dashboard.php');
    }
}

render_public_header('Sign up');
?>
<div class="auth-card">
  <h1>Create your account</h1>
  <p class="sub">No email verification needed. Your school email decides what kind of account you get.</p>
  <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" novalidate data-email-form>
    <?= csrf_field() ?>
    <div class="field">
      <label for="full_name">Full name</label>
      <input id="full_name" name="full_name" type="text" autocomplete="name" required maxlength="120" value="<?= e($name) ?>">
    </div>
    <div class="field">
      <label for="email">School email</label>
      <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" placeholder="20231234@students.nu-laguna.edu.ph" data-domain-check>
      <p class="field-hint" data-domain-hint><?= e(domain_hint()) ?></p>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
      <p class="field-hint">At least <?= MIN_PASSWORD_LENGTH ?> characters.</p>
    </div>
    <div class="field">
      <label for="password2">Confirm password</label>
      <input id="password2" name="password2" type="password" autocomplete="new-password" required>
    </div>
    <button class="btn btn-primary block" type="submit">Create account</button>
  </form>
  <p class="switch">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php render_public_footer();
