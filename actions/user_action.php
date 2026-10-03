<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();
if (!is_post()) {
    redirect('users.php');
}
csrf_check();

$target = get_user((int) post('id')) ?? abort(404);
$isSelf = (int) $target['id'] === (int) $me['id'];

switch (post('do')) {
    case 'make_admin':
        if (email_kind($target['email']) !== 'admin') {
            flash('error', 'Only @' . ADMIN_DOMAINS[0] . ' accounts can be system admins.');
            break;
        }
        db_run("UPDATE users SET role = 'system_admin' WHERE id = ?", [$target['id']]);
        flash('success', $target['full_name'] . ' is now a system admin.');
        break;

    case 'remove_admin':
        if ($isSelf) {
            flash('error', 'You can\'t remove your own admin access.');
            break;
        }
        db_run('UPDATE users SET role = ? WHERE id = ?', [default_role_for($target['email']), $target['id']]);
        sync_user_role((int) $target['id']);
        flash('success', 'Admin access removed.');
        break;

    case 'toggle':
        if ($isSelf) {
            flash('error', 'You can\'t deactivate your own account.');
            break;
        }
        db_run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$target['id']]);
        flash('success', $target['is_active'] ? 'Account deactivated.' : 'Account activated.');
        break;

    case 'password':
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < MIN_PASSWORD_LENGTH) {
            flash('error', 'New password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.');
            break;
        }
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $target['id']]);
        flash('success', 'Password reset for ' . $target['full_name'] . '. Share the new password with them privately.');
        break;

    default:
        abort(404);
}
redirect('users.php' . (post('q') !== '' ? '?q=' . urlencode(post('q')) : ''));
