<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_login();
if (!is_post()) {
    redirect('events.php');
}
csrf_check();

$ev = get_event((int) post('id')) ?? abort(404);
$do = post('do');
$back = safe_back(post('back'), 'event_view.php?id=' . $ev['id']);
$remarks = mb_substr(post('remarks'), 0, 500);

switch ($do) {
    case 'submit':
        can_submit_event($ev) || abort(403);
        db_run("UPDATE events SET status = 'pending', reviewed_by = NULL, reviewed_at = NULL, review_remarks = NULL WHERE id = ?", [$ev['id']]);
        flash('success', 'Event sent to the adviser for approval.');
        break;

    case 'approve':
        can_review_event($ev) || abort(403);
        db_run("UPDATE events SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_remarks = NULLIF(?, '') WHERE id = ?", [$me['id'], $remarks, $ev['id']]);
        flash('success', '"' . $ev['title'] . '" is approved and now visible to students.');
        break;

    case 'disapprove':
        can_review_event($ev) || abort(403);
        if ($remarks === '') {
            flash('error', 'Add a short reason so the officers know what to fix.');
            break;
        }
        db_run("UPDATE events SET status = 'disapproved', reviewed_by = ?, reviewed_at = NOW(), review_remarks = ? WHERE id = ?", [$me['id'], $remarks, $ev['id']]);
        flash('success', 'Event disapproved. The officers can see your reason.');
        break;

    case 'cancel':
        can_cancel_event($ev) || abort(403);
        db_run("UPDATE events SET status = 'cancelled' WHERE id = ?", [$ev['id']]);
        flash('success', 'Event cancelled.');
        break;

    case 'delete':
        can_delete_event($ev) || abort(403);
        db_run('DELETE FROM events WHERE id = ?', [$ev['id']]);
        flash('success', 'Event deleted.');
        redirect('events.php');

    default:
        abort(404);
}
redirect($back);
