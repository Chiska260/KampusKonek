<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();
$errors = [];
$name = $me['full_name'];

if (is_post()) {
    csrf_check();
    if (post('do') === 'profile') {
        $name = post('full_name');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Enter your full name.';
        } else {
            db_run('UPDATE users SET full_name = ? WHERE id = ?', [$name, $me['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif (post('do') === 'password') {
        $row = db_row('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
        if (!password_verify((string) ($_POST['current'] ?? ''), $row['password_hash'])) {
            $errors[] = 'Your current password is wrong.';
        } elseif ($err = check_password_rules((string) ($_POST['password'] ?? ''), (string) ($_POST['password2'] ?? ''))) {
            $errors[] = $err;
        } else {
            db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $me['id']]);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}

$seats = db_all('SELECT m.position, o.id, o.name FROM org_members m JOIN organizations o ON o.id = m.org_id WHERE m.user_id = ? ORDER BY o.name', [$me['id']]);
render_header('Profile', 'profile');
echo page_head('Your profile', 'Your email decides your account type and can\'t be changed.');
?>
<div class="grid-2">
  <section class="panel">
    <div class="profile-top"><?= avatar($me['full_name'], 'lg') ?><div><h2><?= e($me['full_name']) ?></h2><p class="muted"><?= e($me['email']) ?></p><?= badge(ROLE_LABELS[$me['role']], 'navy') ?></div></div>
    <h3>Your seats</h3>
    <?php if (!$seats): ?><p class="muted">No organization seat yet.</p><?php else: ?>
      <ul class="plain-list"><?php foreach ($seats as $s): ?><li><a href="<?= e(url('org_view.php?id=' . $s['id'])) ?>"><?= e($s['name']) ?></a><?= badge(POSITION_LABELS[$s['position']], 'blue') ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" class="form" novalidate>
      <?= csrf_field() ?><input type="hidden" name="do" value="profile">
      <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="120" value="<?= e($name) ?>"></div>
      <button class="btn btn-primary" type="submit">Save name</button>
    </form>
  </section>
  <section class="panel">
    <h2>Change password</h2>
    <?php foreach ($errors as $err): ?><div class="flash flash-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="form" novalidate>
      <?= csrf_field() ?><input type="hidden" name="do" value="password">
      <div class="field"><label for="current">Current password</label><input id="current" name="current" type="password" autocomplete="current-password" required></div>
      <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= MIN_PASSWORD_LENGTH ?>"></div>
      <div class="field"><label for="password2">Confirm new password</label><input id="password2" name="password2" type="password" autocomplete="new-password" required></div>
      <button class="btn btn-primary" type="submit">Change password</button>
    </form>
  </section>
</div>
<?php render_footer();
