<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_login();
if (!is_post()) {
    redirect('announcements.php');
}
csrf_check();

$p = get_post((int) post('id')) ?? abort(404);
$do = post('do');
$back = safe_back(post('back'), 'announcements.php');
$remarks = mb_substr(post('remarks'), 0, 500);

switch ($do) {
    case 'approve':
        can_review_post($p) || abort(403);
        db_run("UPDATE announcements SET status = 'published', reviewed_by = ?, reviewed_at = NOW(), review_remarks = NULL WHERE id = ?", [$me['id'], $p['id']]);
        flash('success', 'Announcement published.');
        break;

    case 'reject':
        can_review_post($p) || abort(403);
        if ($remarks === '') {
            flash('error', 'Add a short reason so the officer knows what to change.');
            break;
        }
        db_run("UPDATE announcements SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_remarks = ? WHERE id = ?", [$me['id'], $remarks, $p['id']]);
        flash('success', 'Announcement not approved. The officer can see your reason.');
        break;

    case 'delete':
        can_delete_post($p) || abort(403);
        db_run('DELETE FROM announcements WHERE id = ?', [$p['id']]);
        flash('success', 'Announcement deleted.');
        break;

    default:
        abort(404);
}
redirect($back);
